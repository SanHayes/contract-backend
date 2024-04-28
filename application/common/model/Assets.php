<?php

namespace app\common\model;

use think\db\Where;
use think\Model;

class Assets extends Model
{

    protected $autoWriteTimestamp = 'datetime';
    protected $name = 'assets';

    public static function createNewAccount(User $user)
    {
        $tokens = Token::getLists($user['token']['chain']);
        foreach ($tokens as $token) {
            $map = new Where();
            $map['user_id'] = $user['id'];
            $map['token_id'] = $token['id'];
            $row = static::where($map)
                ->findOrEmpty();
            if ($row->isEmpty()) {
                static::create(['user_id' => $user['id'], 'token_id' => $token['id']]);
            }
        }
    }

    public function user()
    {
        return $this->hasMany(User::class, 'id', 'user_id');
    }

    public function token()
    {
        return $this->belongsTo(Token::class);
    }

}
