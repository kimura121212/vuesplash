<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StorePhoto;
use App\Photo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Db;
use Illuminate\Support\Facades\Storage;


class PhotoController extends Controller
{
    public function __construct()
    {
        //認証がひつよ
        $this->middleware('auth')->except(['index', 'download']);
    }
    //写真投稿
    public function create(StorePhoto $request)
    {
        //投稿写真の拡張子を取得する
        $extension = $request->photo->extension();

        $photo = new Photo();

        //インスタンス生成時に割り振られたランダムなID値と
        //本来の拡張子を組み合わせてファイル名とする
        $photo->filename = $photo->id . '.' . $extension;

        //S3にファイルを保存する
        // 第三引数の'public'はファイルを公開状態で保存するため
        Storage::cloud()->putFileAs('', $request->photo, $photo->filename, 'public');

        //データベースエラー時にファイル削除を行うため
        //トランザクションを利用
        DB::beginTransaction();

        //データベースエラー時にファイル削除を行うため
        //トランザクションを利用する
        DB::beginTransaction();

        try {
            Auth::user()->photos()->save($photo);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollback();
            //DBとの不整合を避けるためアップロードしたファイルを削除
            Storage::cloud()->delete($photo->filename);
            throw $exception;
        }

        //リソースの新規作成なので、レスポンスコードは201(CREATED)を返却する
        return response($photo, 201);
    }

    // 写真一覧
    public function index()
    {
        $photos = Photo::with(['owner'])
            ->orderBy(Photo::CREATED_AT, 'desc')->paginate();

        return $photos;
    }

    // 写真ダウンロード
    // @param Photo $photo
    // @return \\Illuminate\Http\Response
    public function download(Photo $photo)
    {
        // 写真の存在チェック
        if (! Storage::cloud()->exists($photo->filename)){
            abort(404);
        }

        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment: filename="' . $photo->filename . '"',
        ];

        return response(Storage::cloud()->get($photo->filename), 200, $headers);
    }
}
