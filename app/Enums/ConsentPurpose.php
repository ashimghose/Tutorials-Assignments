<?php

namespace App\Enums;

/**
 * What a stored consent covers (DPDP Act). A parent consents for their own
 * account and separately for each child; an academic student for themselves.
 * A music learner consents for themselves (18+), or a parent approves an
 * under-18 learner's account by email (MUS-3). A teacher or expert who
 * signs up consents for themselves (HIR-8).
 */
enum ConsentPurpose: string
{
    case ParentAccount = 'parent_account';
    case ChildData = 'child_data';
    case AcademicSelf = 'academic_self';
    case MusicSelf = 'music_self';
    case MusicGuardian = 'music_guardian';
    case TeacherSelf = 'teacher_self';
}
