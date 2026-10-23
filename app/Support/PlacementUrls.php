<?php

namespace App\Support;

use App\Models\User;

/**
 * Where placement staff land: admins work in Filament, recruitment managers in their own dashboard.
 */
class PlacementUrls
{
    public static function project(object $viewer, int $projectId): string
    {
        return self::forRecruitmentManager($viewer)
            ? url('/recruitment-manager/projects/'.$projectId)
            : url('/bv-admin/recruiter-projects/'.$projectId);
    }

    public static function candidates(object $viewer): string
    {
        return self::forRecruitmentManager($viewer)
            ? url('/recruitment-manager/candidates')
            : url('/bv-admin/candidates');
    }

    private static function forRecruitmentManager(object $viewer): bool
    {
        return $viewer instanceof User && $viewer->isRecruitmentManager();
    }
}
