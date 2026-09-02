<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Media;

class MediaController extends Controller
{
    public function index()
    {
        $media = Media::orderBy('created_at', 'desc')->get()->map(function ($m) {
            return [
                'id' => $m->id,
                'fileName' => $m->file_name,
                'filePath' => $m->file_path,
                'mimeType' => $m->mime_type,
                'fileSize' => $m->file_size,
                'category' => $m->category,
                'uploadedBy' => $m->uploaded_by,
                'uploadedAt' => $m->created_at
            ];
        });

        return response()->json(['success' => true, 'data' => $media]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'filePath' => 'required|string',
            'fileName' => 'required|string'
        ]);

        $m = Media::create([
            'file_name' => $request->fileName,
            'file_path' => $request->filePath,
            'mime_type' => $request->mimeType ?? 'image/jpeg',
            'file_size' => $request->fileSize ?? 0,
            'category' => $request->category ?? 'general',
            'uploaded_by' => $request->uploadedBy ?? 1
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã tải lên media thành công!',
            'data' => ['id' => $m->id]
        ]);
    }

    public function destroy($id)
    {
        $media = Media::find($id);
        if ($media) {
            $media->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa media thành công!'
        ]);
    }
}