<?php

namespace App\Enums;

enum SettingKeys: string
{
    case COMMISSION_AMOUNT = 'commission_amount';
    case COMMISSION_TYPE = 'commission_type';
    case SMS_GATEWAY = 'sms_gateway';
    case OTP_GATEWAY = 'otp_gateway';
    
    // Points Settings
    case LOYALTY_POINTS = 'loyalty_points';
    case FIRST_BOOKING_POINTS = 'first_booking_points';
    case REVIEW_POINTS = 'review_points';
    case REFERRAL_POINTS = 'referral_points';
    case POINTS_TO_WALLET_RATE = 'points_to_wallet_rate';
    case MIN_POINTS_FOR_CONVERSION = 'min_points_for_conversion';
}
