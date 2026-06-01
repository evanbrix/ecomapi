<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSliderRequest;
use App\Http\Requests\UpdateSliderRequest;
use App\Http\Resources\SliderResource;
use App\Models\Slider;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SliderController extends Controller
{
    public function index(): JsonResponse
    {
        $sliders = Slider::orderBy('sort_order')->orderBy('id')->get();

        return $this->success(SliderResource::collection($sliders));
    }

    public function store(StoreSliderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $uploaded = $request->file('image')->store('sliders', 'public');
        $data['image'] = $uploaded;

        try {
            $slider = DB::transaction(fn () => Slider::create($data));
        } catch (Throwable $e) {
            Storage::disk('public')->delete($uploaded);
            throw $e;
        }

        return $this->success(new SliderResource($slider), 'Slider created.', 201);
    }

    public function show(Slider $slider): JsonResponse
    {
        return $this->success(new SliderResource($slider));
    }

    public function update(UpdateSliderRequest $request, Slider $slider): JsonResponse
    {
        $data = $request->validated();
        $oldImage = $slider->image;
        $uploaded = null;

        if ($request->hasFile('image')) {
            $uploaded = $request->file('image')->store('sliders', 'public');
            $data['image'] = $uploaded;
        }

        try {
            DB::transaction(fn () => $slider->update($data));
        } catch (Throwable $e) {
            if ($uploaded) {
                Storage::disk('public')->delete($uploaded);
            }
            throw $e;
        }

        if ($uploaded && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return $this->success(new SliderResource($slider->fresh()), 'Slider updated.');
    }

    public function destroy(Slider $slider): JsonResponse
    {
        $files = array_filter([$slider->image]);

        DB::transaction(fn () => $slider->delete());

        foreach ($files as $file) {
            Storage::disk('public')->delete($file);
        }

        return $this->success(null, 'Slider deleted.');
    }
}
