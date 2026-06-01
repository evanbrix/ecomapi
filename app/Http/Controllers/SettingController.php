<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $imageKeys = ['logo', 'favicon', 'og_image'];

        $settings = SiteSetting::all()->mapWithKeys(function (SiteSetting $row) use ($imageKeys) {
            $value = $row->value;

            if (in_array($row->key, $imageKeys, true) && $value) {
                $value = Storage::url($value);
            }

            return [$row->key => $value];
        });

        return $this->success($settings);
    }
}
