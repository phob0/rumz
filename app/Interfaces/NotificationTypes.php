<?php

namespace App\Interfaces;

interface NotificationTypes
{
    const ROOM_INVITATION = 'roomInvitation';
    const ROOM_REJECTION = 'roomRejection';
    const ROOM_ACCEPTANCE = 'roomAcceptance';

    const ROOM_APPROVAL_SUBSCRIBER = 'roomApprovalSubscriber';
    const ROOM_REJECTION_SUBSCRIBER = 'roomRejectionSubscriber';
    const ROOM_REPORT = 'roomReport';
    const ROOM_SUBSCRIPTION_APPROVAL = 'roomSubscriptionApproval';
    const ROOM_SUBSCRIPTION_PAYMENT_INFO = 'roomSubscriptionPaymentInfo';

    const ADMIN_ROOM_INVITATION = 'adminRoomInvitation';
    const ADMIN_ROOM_REJECTION = 'adminRoomRejection';
    const ADMIN_ROOM_ACCEPTANCE = 'adminRoomAcceptance';

    const FRIEND_INVITATION = 'friendInvitation';
    const FRIEND_REJECTION = 'friendRejection';
    const FRIEND_ACCEPTANCE = 'friendAcceptance';

    const MEMBER_INVITATION = 'memberInvitation';
    const NEW_MEMBER = 'newMember';
    const MEMBER_REMOVAL = 'memberRemoval';
    const BAN_MEMBER = 'banMember';
    const UNBAN_MEMBER = 'banMember';
}
