<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SubmissionGuard
{
    public static function firstTime(Request $request): bool
    {
        $id = $request->input('_submission_id');

        if (! is_string($id) || trim($id) === '') {
            return true;
        }

        return Cache::add('submission:'.trim($id), true, 600);
    }
}
