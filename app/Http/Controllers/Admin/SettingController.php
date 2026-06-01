<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SettingController extends Controller
{
    private const IMAGE_KEYS = ['logo', 'favicon', 'og_image'];

    public function index(): JsonResponse
    {
        $settings = SiteSetting::all()->mapWithKeys(function (SiteSetting $row) {
            $value = $row->value;

            if (in_array($row->key, self::IMAGE_KEYS, true) && $value) {
                $value = Storage::url($value);
            }

            return [$row->key => $value];
        });

        return $this->success($settings);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $uploaded = [];
        $oldFiles = [];

        foreach (self::IMAGE_KEYS as $key) {
            if ($request->hasFile($key)) {
                $existing = SiteSetting::where('key', $key)->value('value');
                if ($existing) {
                    $oldFiles[] = $existing;
                }

                $uploaded[] = $data[$key] = $request->file($key)->store('settings', 'public');
            }
        }

        try {
            DB::transaction(function () use ($data) {
                foreach ($data as $key => $value) {
                    SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
                }
            });
        } catch (Throwable $e) {
            foreach ($uploaded as $p) {
                Storage::disk('public')->delete($p);
            }
            throw $e;
        }

        foreach ($oldFiles as $f) {
            Storage::disk('public')->delete($f);
        }

        return $this->index();
    }
}
