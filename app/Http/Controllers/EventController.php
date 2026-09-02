<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::all()->map(function ($ev) {
            return [
                'id' => $ev->id,
                'title' => $ev->title,
                'location' => $ev->location,
                'startTime' => $ev->start_time,
                'endTime' => $ev->end_time,
                'description' => $ev->description,
                'bannerImage' => $ev->banner_image,
                'status' => $ev->status,
                'attendeesCount' => $ev->attendees_count,
                'createdAt' => $ev->created_at
            ];
        });

        return response()->json(['success' => true, 'data' => $events]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'location' => 'required|string'
        ]);

        $event = Event::create([
            'title' => $request->title,
            'location' => $request->location,
            'start_time' => $request->startTime ?? now()->addDays(7),
            'end_time' => $request->endTime ?? null,
            'description' => $request->description ?? '',
            'attendees_count' => $request->attendeesCount ?? 0,
            'status' => $request->status ?? 'upcoming'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo sự kiện mới!',
            'data' => ['id' => $event->id]
        ]);
    }

    public function destroy($id)
    {
        $event = Event::find($id);
        if ($event) {
            $event->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa sự kiện thành công!'
        ]);
    }
}