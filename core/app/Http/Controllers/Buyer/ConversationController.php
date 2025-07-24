<?php

namespace App\Http\Controllers\Buyer;

use App\Events\LiveChat;
use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Conversation;
use App\Models\Message;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConversationController extends Controller
{
    public function index()
    {
        $pageTitle     = "Chat";
        $buyer         = auth()->guard('buyer')->user();
        $conversation  = null;
        $freelancer    = null;
        $messages      = null;
        $conversations = Conversation::where('buyer_id', $buyer->id)->whereHas('messages')->with(['user', 'messages', 'buyer'])->distinct()->orderBy('created_at', 'DESC')->get();
        return view('Template::buyer.conversation.index', compact('pageTitle', 'buyer', 'freelancer', 'messages', 'conversations'));
    }

    public function bidChat($id)
    {
        $pageTitle = "Conversation";
        $buyer     = auth()->guard('buyer')->user();

        $bid = Bid::where('id', $id)->firstOrFail();
        $job = $bid->job;

        $freelancer   = $bid->user;
        $conversation = Conversation::where('buyer_id', $buyer->id)->with(['user', 'buyer', 'messages'])
            ->where('user_id', $freelancer->id)
            ->first();

        if (!$conversation) {
            $conversation           = new Conversation();
            $conversation->buyer_id = $buyer->id;
            $conversation->user_id  = $freelancer->id;
            $conversation->save();

            $job->interviews += 1;
            $job->save();
        }

        $messages = $conversation->messages;
        $buyer    = auth()->guard('buyer')->user();

        $conversations = Conversation::where('buyer_id', $buyer->id)->with(['user', 'messages', 'buyer'])
            ->orderBy('updated_at', 'desc')->distinct()
            ->get();

        $id = $conversation->id;
        return view('Template::buyer.conversation.index', compact('pageTitle', 'buyer', 'freelancer', 'messages', 'conversation', 'conversations', 'id'));
    }

    public function conversation($id)
    {

        $pageTitle    = "Conversation";
        $buyer        = auth()->guard('buyer')->user();
        $conversation = Conversation::where('id', $id)->with(['user', 'buyer', 'messages'])->firstOrFail();
        $freelancer   = @$conversation->user;

        // Mark unread messages as read
        Message::where('conversation_id', $conversation->id)->whereNull('buyer_read_at')->where(function ($query) use ($freelancer) {
            $query->where('user_id', $freelancer->id);
        })->update(['buyer_read_at' => now()]);

        $messages = $conversation->messages;

        $buyer         = auth()->guard('buyer')->user();
        $conversations = Conversation::where('buyer_id', $buyer->id)
        ->whereHas('messages')
            ->with([
                'user',
                'buyer',
                'messages',
            ])
            ->orderByDesc(
                Message::select('created_at')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->latest()
                    ->take(1)
            )
            ->get();

        Message::whereHas('conversation', function ($query) use ($buyer) {
            $query->where('buyer_id', $buyer->id);
        })
            ->whereNull('buyer_read_at')
            ->update(['buyer_read_at' => now()]);

        $id = $conversation->id;
        return view('Template::buyer.conversation.index', compact('pageTitle', 'buyer', 'freelancer', 'messages', 'conversation', 'conversations', 'id'));
    }

    public function conversationStore(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'message'         => 'required',
            'message_files'   => ['nullable', 'array', 'max:10'],
            'message_files.*' => ['nullable', 'max:2048', new FileTypeValidate(['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG', 'pdf', 'PDF', 'docx', 'DOCX', 'doc', 'DOC'])],
        ]);

        if ($validation->fails()) {
            return responseError('validation_error', $validation->errors()->all());
        }

        $conversation = Conversation::unblock()->findOrFail($id);
        if (!($conversation)) {
            $notify[] = 'Conversation not found';
            return responseError('conversation_not_found', $notify);
        }

        if (!($request->message_files) && !($request->message)) {
            $notify[] = 'Message field is required';
            return responseError('validation_required', $notify);
        }

        $data = initializePusher();
        if (!$data) {
            $notify[] = 'Pusher connection is required';
            return responseError('connection_required', $notify);
        }

        if ($request->message_files) {
            foreach ($request->message_files as $messageFile) {
                try {
                    $message_files[] = fileUploader($messageFile, getFilePath('message'));
                } catch (\Exception $exp) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Couldn\'t upload your files',
                    ]);
                }
            }
        }

        $message                  = new Message();
        $message->message         = $request->message;
        $message->files           = @$message_files;
        $message->conversation_id = $id;
        $message->buyer_id        = $conversation->buyer_id;
        $message->buyer_read_at   = now();
        $message->save();

        event(new LiveChat($message));

        $notify[] = 'Successfully sent message';
        return responseSuccess('sent_message', $notify);
    }

    public function download($filename)
    {
        $filePath = public_path(getFilePath('message') . $filename);
        if (file_exists($filePath)) {
            return response()->download($filePath);
        }
        abort(404, 'File not found.');
    }

    public function blockStatus($id)
    {
        return Conversation::changeStatus($id);
    }
}
