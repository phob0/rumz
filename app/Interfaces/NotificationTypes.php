<?php

namespace App\Interfaces;

interface NotificationTypes
{
    const ROOM_INVITATION = 'roomInvitation';
    const ROOM_REJECTION = 'roomRejection';
    const ROOM_ACCEPTANCE = 'roomAcceptance';
    const ADMIN_ROOM_INVITATION = 'adminRoomInvitation';
    const ADMIN_ROOM_REJECTION = 'adminRoomRejection';
    const ADMIN_ROOM_ACCEPTANCE = 'adminRoomAcceptance';
}
