<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute を承認する必要があります。',
    'accepted_if' => ':other が :value の場合、:attribute を承認する必要があります。',
    'active_url' => ':attribute は有効な URL である必要があります。',
    'after' => ':attribute は :date より後の日付である必要があります。',
    'after_or_equal' => ':attribute は :date 以降の日付である必要があります。',
    'alpha' => ':attribute は英字のみで構成される必要があります。',
    'alpha_dash' => ':attribute は英字、数字、ダッシュ、アンダースコアのみで構成される必要があります。',
    'alpha_num' => ':attribute は英字と数字のみで構成される必要があります。',
    'any_of' => ':attribute が無効です。',
    'array' => ':attribute は配列である必要があります。',
    'ascii' => ':attribute はシングルバイトの英数字と記号のみで構成される必要があります。',
    'before' => ':attribute は :date より前の日付である必要があります。',
    'before_or_equal' => ':attribute は :date 以前の日付である必要があります。',
    'between' => [
        'array' => ':attribute は :min 〜 :max 個の要素を含む必要があります。',
        'file' => ':attribute は :min 〜 :max キロバイトの間である必要があります。',
        'numeric' => ':attribute は :min 〜 :max の間である必要があります。',
        'string' => ':attribute は :min 〜 :max 文字の間である必要があります。',
    ],
    'boolean' => ':attribute は true か false である必要があります。',
    'can' => ':attribute に無効な値が含まれています。',
    'confirmed' => ':attribute の確認が一致しません。',
    'contains' => ':attribute に必要な値が含まれていません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attribute は有効な日付である必要があります。',
    'date_equals' => ':attribute は :date と同じ日付である必要があります。',
    'date_format' => ':attribute は :format 形式と一致する必要があります。',
    'decimal' => ':attribute は小数点以下 :decimal 桁である必要があります。',
    'declined' => ':attribute を拒否する必要があります。',
    'declined_if' => ':other が :value の場合、:attribute を拒否する必要があります。',
    'different' => ':attribute と :other は異なる必要があります。',
    'digits' => ':attribute は :digits 桁である必要があります。',
    'digits_between' => ':attribute は :min 〜 :max 桁の間である必要があります。',
    'dimensions' => ':attribute の画像サイズが無効です。',
    'distinct' => ':attribute に重複する値が含まれています。',
    'doesnt_contain' => ':attribute には次のいずれも含めることはできません: :values。',
    'doesnt_end_with' => ':attribute は次のいずれかで終わることはできません: :values。',
    'doesnt_start_with' => ':attribute は次のいずれかで始まることはできません: :values。',
    'email' => ':attribute は有効なメールアドレスである必要があります。',
    'encoding' => ':attribute は :encoding でエンコードされている必要があります。',
    'ends_with' => ':attribute は次のいずれかで終わる必要があります: :values。',
    'enum' => '選択された :attribute は無効です。',
    'exists' => '選択された :attribute は無効です。',
    'extensions' => ':attribute は次のいずれかの拡張子である必要があります: :values。',
    'file' => ':attribute はファイルである必要があります。',
    'filled' => ':attribute は値を持つ必要があります。',
    'gt' => [
        'array' => ':attribute は :value 個より多い要素を含む必要があります。',
        'file' => ':attribute は :value キロバイトより大きい必要があります。',
        'numeric' => ':attribute は :value より大きい必要があります。',
        'string' => ':attribute は :value 文字より多い必要があります。',
    ],
    'gte' => [
        'array' => ':attribute は :value 個以上の要素を含む必要があります。',
        'file' => ':attribute は :value キロバイト以上である必要があります。',
        'numeric' => ':attribute は :value 以上である必要があります。',
        'string' => ':attribute は :value 文字以上である必要があります。',
    ],
    'hex_color' => ':attribute は有効な16進数カラーコードである必要があります。',
    'image' => ':attribute は画像である必要があります。',
    'in' => '選択された :attribute は無効です。',
    'in_array' => ':attribute は :other に存在する必要があります。',
    'in_array_keys' => ':attribute には次のキーのいずれかを含む必要があります: :values。',
    'integer' => ':attribute は整数である必要があります。',
    'ip' => ':attribute は有効な IP アドレスである必要があります。',
    'ipv4' => ':attribute は有効な IPv4 アドレスである必要があります。',
    'ipv6' => ':attribute は有効な IPv6 アドレスである必要があります。',
    'json' => ':attribute は有効な JSON 文字列である必要があります。',
    'list' => ':attribute はリストである必要があります。',
    'lowercase' => ':attribute は小文字である必要があります。',
    'lt' => [
        'array' => ':attribute は :value 個未満の要素を含む必要があります。',
        'file' => ':attribute は :value キロバイト未満である必要があります。',
        'numeric' => ':attribute は :value 未満である必要があります。',
        'string' => ':attribute は :value 文字未満である必要があります。',
    ],
    'lte' => [
        'array' => ':attribute は :value 個以下の要素を含む必要があります。',
        'file' => ':attribute は :value キロバイト以下である必要があります。',
        'numeric' => ':attribute は :value 以下である必要があります。',
        'string' => ':attribute は :value 文字以下である必要があります。',
    ],
    'mac_address' => ':attribute は有効な MAC アドレスである必要があります。',
    'max' => [
        'array' => ':attribute は :max 個を超える要素を含むことはできません。',
        'file' => ':attribute は :max キロバイトを超えることはできません。',
        'numeric' => ':attribute は :max を超えることはできません。',
        'string' => ':attribute は :max 文字を超えることはできません。',
    ],
    'max_digits' => ':attribute は :max 桁を超えることはできません。',
    'mimes' => ':attribute は次のいずれかのファイルタイプである必要があります: :values。',
    'mimetypes' => ':attribute は次のいずれかのファイルタイプである必要があります: :values。',
    'min' => [
        'array' => ':attribute は少なくとも :min 個の要素を含む必要があります。',
        'file' => ':attribute は少なくとも :min キロバイトである必要があります。',
        'numeric' => ':attribute は少なくとも :min である必要があります。',
        'string' => ':attribute は少なくとも :min 文字である必要があります。',
    ],
    'min_digits' => ':attribute は少なくとも :min 桁である必要があります。',
    'missing' => ':attribute は存在してはいけません。',
    'missing_if' => ':other が :value の場合、:attribute は存在してはいけません。',
    'missing_unless' => ':other が :value でない限り、:attribute は存在してはいけません。',
    'missing_with' => ':values が存在する場合、:attribute は存在してはいけません。',
    'missing_with_all' => ':values がすべて存在する場合、:attribute は存在してはいけません。',
    'multiple_of' => ':attribute は :value の倍数である必要があります。',
    'not_in' => '選択された :attribute は無効です。',
    'not_regex' => ':attribute の形式が無効です。',
    'numeric' => ':attribute は数値である必要があります。',
    'password' => [
        'letters' => ':attribute には少なくとも1つの文字を含む必要があります。',
        'mixed' => ':attribute には少なくとも1つの大文字と1つの小文字を含む必要があります。',
        'numbers' => ':attribute には少なくとも1つの数字を含む必要があります。',
        'symbols' => ':attribute には少なくとも1つの記号を含む必要があります。',
        'uncompromised' => '指定された :attribute はデータ漏洩に含まれています。別の :attribute を選択してください。',
    ],
    'present' => ':attribute が存在する必要があります。',
    'present_if' => ':other が :value の場合、:attribute が存在する必要があります。',
    'present_unless' => ':other が :value でない限り、:attribute が存在する必要があります。',
    'present_with' => ':values が存在する場合、:attribute が存在する必要があります。',
    'present_with_all' => ':values がすべて存在する場合、:attribute が存在する必要があります。',
    'prohibited' => ':attribute は禁止されています。',
    'prohibited_if' => ':other が :value の場合、:attribute は禁止されています。',
    'prohibited_if_accepted' => ':other が承認された場合、:attribute は禁止されています。',
    'prohibited_if_declined' => ':other が拒否された場合、:attribute は禁止されています。',
    'prohibited_unless' => ':other が :values にない限り、:attribute は禁止されています。',
    'prohibits' => ':attribute は :other の存在を禁止します。',
    'regex' => ':attribute の形式が無効です。',
    'required' => ':attribute は必須です。',
    'required_array_keys' => ':attribute には次のエントリが含まれる必要があります: :values。',
    'required_if' => ':other が :value の場合、:attribute は必須です。',
    'required_if_accepted' => ':other が承認された場合、:attribute は必須です。',
    'required_if_declined' => ':other が拒否された場合、:attribute は必須です。',
    'required_unless' => ':other が :values にない限り、:attribute は必須です。',
    'required_with' => ':values が存在する場合、:attribute は必須です。',
    'required_with_all' => ':values がすべて存在する場合、:attribute は必須です。',
    'required_without' => ':values が存在しない場合、:attribute は必須です。',
    'required_without_all' => ':values がすべて存在しない場合、:attribute は必須です。',
    'same' => ':attribute は :other と一致する必要があります。',
    'size' => [
        'array' => ':attribute は :size 個の要素を含む必要があります。',
        'file' => ':attribute は :size キロバイトである必要があります。',
        'numeric' => ':attribute は :size である必要があります。',
        'string' => ':attribute は :size 文字である必要があります。',
    ],
    'starts_with' => ':attribute は次のいずれかで始まる必要があります: :values。',
    'string' => ':attribute は文字列である必要があります。',
    'timezone' => ':attribute は有効なタイムゾーンである必要があります。',
    'unique' => ':attribute は既に使用されています。',
    'uploaded' => ':attribute のアップロードに失敗しました。',
    'uppercase' => ':attribute は大文字である必要があります。',
    'url' => ':attribute は有効な URL である必要があります。',
    'ulid' => ':attribute は有効な ULID である必要があります。',
    'uuid' => ':attribute は有効な UUID である必要があります。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'is_confirmed' => [
            'accepted' => '顧客への通知と署名取得を確認するチェックボックスにチェックを入れてください。',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'username' => 'ユーザー名',
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'current_password' => '現在のパスワード',
        'password_confirmation' => 'パスワード確認',
        'phone' => '電話番号',
        'machine_id' => '機台',
        'category' => 'カテゴリ',
        'maintenance_at' => 'メンテナンス日',
        'content' => 'メンテナンス内容',
        'is_confirmed' => '確認チェックボックス',
    ],

];
