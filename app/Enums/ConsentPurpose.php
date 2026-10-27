<?php

namespace App\Enums;

/**
 * What a stored consent covers (DPDP Act). A parent consents for their own
 * account and separately for each child; an academic student for themselves.
 */
enum ConsentPurpose: string
{
    case ParentAccount = 'parent_account';
    case ChildData = 'child_data';
    case AcademicSelf = 'academic_self';
}
