<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Events\LiveChat;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Rules\FileTypeValidate;
use Illuminate\Support\Facades\Validator;

class ConversationController extends Controller
{

    public function index($id = 0)
    {
        $pageTitle = "Chat";
        $freelancer = auth()->user();
        $conversation = NULL;
        $messages = NULL;
        $buyer = NULL;
        $id = $id;

        if ($id) {
            $conversation = Conversation::unblock()->where('id', $id)->with(['job'])->with(['user', 'buyer', 'messages'])->first();
            if (!$conversation) {
                $notify[] = ['error', 'You are temporary blocked this conversation'];
                return back()->withNotify($notify);
            }

            $buyer =  @$conversation?->buyer;
            $id = $conversation?->id;

            // Mark unread messages as read
            Message::whereNull('read_at')
            ->where(function ($query) use ($conversation, $freelancer, $buyer) {
                $query->where('conversation_id', $conversation->id)
                    ->where('buyer_id', $freelancer->id)
                    ->orWhereHas('conversation', fn($q) => $q->where('user_id', $buyer->id));
            })
            ->update(['read_at' => now()]);

            $messages = $conversation->messages;
        }

        $conversations = Conversation::where('user_id', $freelancer->id)
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

            Message::whereHas('conversation', function ($query) use ($freelancer) {
                $query->where('user_id', $freelancer->id);
            })
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('Template::user.conversation.index', compact('pageTitle', 'buyer', 'freelancer', 'messages', 'conversation', 'conversations', 'id'));
    }

    public function conversationStore(Request $request, $id)
    {
        $validation  = Validator::make($request->all(), [
            'message'                => 'required',
            'message_files'          => ['nullable', 'array', 'max:10'],
            'message_files.*'        => ['nullable', 'max:2048', new FileTypeValidate(['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG', 'pdf', 'PDF', 'docx', 'DOCX', 'doc', 'DOC'])],
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
            foreach ($request->message_files as $message_file) {
                try {
                    $message_files[] = fileUploader($message_file, getFilePath('message'));
                } catch (\Exception $exp) {
                    $notify[] = 'Couldn\'t upload your files: ' . $exp;
                    return responseError('upload_failed', $notify);
                }
            }
        }

        $message          = new Message();
        $message->message = $request->message;
        $message->files   = @$message_files;
        $message->conversation_id = $id;
        $message->user_id    = $conversation->user_id;
        $message->read_at    = now();
        $message->save();

        event(new LiveChat($message));


        $notify[] = 'Successfully sent message';
        return  responseSuccess('sent_message', $notify);
    }
}
