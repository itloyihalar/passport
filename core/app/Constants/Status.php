<?php

namespace App\Constants;

class Status
{

    const ENABLE = 1;
    const DISABLE = 0;

    const YES = 1;
    const NO = 0;

    const VERIFIED = 1;
    const UNVERIFIED = 0;

    const PAYMENT_INITIATE = 0;
    const PAYMENT_SUCCESS = 1;
    const PAYMENT_PENDING = 2;
    const PAYMENT_REJECT = 3;

    const TICKET_OPEN = 0;
    const TICKET_ANSWER = 1;
    const TICKET_REPLY = 2;
    const TICKET_CLOSE = 3;

    const PRIORITY_LOW = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_HIGH = 3;

    const USER_ACTIVE = 1;
    const USER_BAN = 0;

    const KYC_UNVERIFIED = 0;
    const KYC_PENDING = 2;
    const KYC_VERIFIED = 1;

    const GOOGLE_PAY = 5001;

    const CUR_BOTH = 1;
    const CUR_TEXT = 2;
    const CUR_SYM = 3;

  
    const JOB_DRAFT = 0;
    const JOB_PUBLISH = 1;
    const JOB_PROCESSING = 2;
    const JOB_COMPLETED = 3;
    const JOB_FINISHED = 4;


    const JOB_PENDING = 0;
    const JOB_APPROVED = 1;
    const JOB_REJECTED = 3;


    const BID_PENDING = 0;
    const BID_ACCEPTED = 1;
    const BID_COMPLETED = 2;
    const BID_REJECTED = 3;
    const BID_WITHDRAW = 4;

    const PROJECT_RUNNING = 1;
    const PROJECT_COMPLETED = 2;
    const PROJECT_REJECTED = 3;
    const PROJECT_BUYER_REVIEW = 4;
    const PROJECT_REPORTED = 5;

    const BLOCK = 1;
    const UNBLOCK = 0;

    const SCOPE_LARGE = 1;
    const SCOPE_MEDIUM = 2;
    const SCOPE_SMALL = 3;

    const SKILL_PRO = 1;
    const SKILL_EXPERT = 2;
    const SKILL_INTERMEDIATE = 3;
    const SKILL_ENTRY = 4;

    const JOB_LONGEVITY_WEEK = 1;
    const JOB_LONGEVITY_MONTH = 2;
    const JOB_LONGEVITY_ABOVE_MONTH = 3;
    const JOB_LONGEVITY_MORE_MONTH = 4;
}
