<?php

namespace App\Enums;

enum CompanyLegalDocType: string
{
    case TOURISM_LICENSE = 'tourism_license';
    case COMMERCIAL_REGISTRATION = 'commercial_registration';
    case FOOD_SAFETY_CERT = 'food_safety_cert';
    case CATERING_PERMIT = 'catering_permit';
}

