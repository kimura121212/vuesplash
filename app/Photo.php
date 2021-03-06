<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    //プライマリーキーの型
    protected $keyType = 'string';

    // JSONに含める属性
    protected $appends = [
        'url',
    ];

    // JSONに含めない属性
    // protected $hidden = [
    //     'user_id', 'filename',
    //     self::CREATED_AT, self.UPDATED_AT,
    // ];

    // JSONに含める属性
    protected $visible = [
        'id', 'owner', 'url',
    ];


    // IDの桁数
    const ID_LENGTH = 12;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (! array_get($this->attributes, 'id')) {
            $this->setId();
        }
    }

    // ランダムなID値をid属性に代入する
    private function setId()
    {
        $this->attributes['id'] = $this->getRandomId();
    }

    //ランダムなID値を生成する
    private function getRandomId()
    {
        $characters = array_merge(
            range(0,9), range('a', 'z'),
            range('A', 'Z'), ['-', '_']
        );

        $length = count($characters);

        $id = "";

        for ($i = 0; $i < self::ID_LENGTH; $i++) {
            $id = $characters[random_int(0, $length - 1)];
        }

        return $id;
    }

    public function owner()
    {
        return $this->belongsTo('App\User', 'user_id', 'id', 'users');
    }

    // アクセサ - url 
    // @return string

    public function getUrlAttribute()
    {
        return Storage::cloud()->url($this->attributes['filename']);
    }
}
