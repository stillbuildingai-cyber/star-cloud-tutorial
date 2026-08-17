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

    'accepted' => '必須接受 :attribute。',
    'accepted_if' => '當 :other 為 :value 時，必須接受 :attribute。',
    'active_url' => ':attribute 並非有效的 URL。',
    'after' => ':attribute 必須在 :date 之後。',
    'after_or_equal' => ':attribute 必須在 :date 之後或相等。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_dash' => ':attribute 只能包含字母、數字、破折號與底線。',
    'alpha_num' => ':attribute 只能包含字母與數字。',
    'any_of' => ':attribute 無效。',
    'array' => ':attribute 必須是一個陣列。',
    'ascii' => ':attribute 只能包含單字節的字母、數字與符號。',
    'before' => ':attribute 必須在 :date 之前。',
    'before_or_equal' => ':attribute 必須在 :date 之前或相等。',
    'between' => [
        'array' => ':attribute 必須包含 :min 至 :max 個項目。',
        'file' => ':attribute 必須介於 :min 至 :max KB 之間。',
        'numeric' => ':attribute 必須介於 :min 至 :max 之間。',
        'string' => ':attribute 必須介於 :min 至 :max 個字元之間。',
    ],
    'boolean' => ':attribute 必須為布林值。',
    'can' => ':attribute 包含未授權的值。',
    'confirmed' => ':attribute 確認欄位不符。',
    'contains' => ':attribute 缺少必要的值。',
    'current_password' => '目前的密碼不正確。',
    'date' => ':attribute 並非有效的日期。',
    'date_equals' => ':attribute 必須等於 :date。',
    'date_format' => ':attribute 不符合格式 :format。',
    'decimal' => ':attribute 必須有 :decimal 位小數。',
    'declined' => ':attribute 必須拒絕。',
    'declined_if' => '當 :other 為 :value 時，:attribute 必須拒絕。',
    'different' => ':attribute 與 :other 必須不同。',
    'digits' => ':attribute 必須是 :digits 位數。',
    'digits_between' => ':attribute 必須介於 :min 至 :max 位數之間。',
    'dimensions' => ':attribute 圖片尺寸無效。',
    'distinct' => ':attribute 欄位含有重複的值。',
    'doesnt_contain' => ':attribute 不得包含以下任何值：:values。',
    'doesnt_end_with' => ':attribute 不得以以下任何值結尾：:values。',
    'doesnt_start_with' => ':attribute 不得以以下任何值開頭：:values。',
    'email' => ':attribute 必須是有效的電子郵件地址。',
    'encoding' => ':attribute 必須以 :encoding 編碼。',
    'ends_with' => ':attribute 必須以以下任一值結尾：:values。',
    'enum' => '所選的 :attribute 無效。',
    'exists' => '所選的 :attribute 無效。',
    'extensions' => ':attribute 必須是以下副檔名之一：:values。',
    'file' => ':attribute 必須是一個檔案。',
    'filled' => ':attribute 不能為空。',
    'gt' => [
        'array' => ':attribute 必須包含超過 :value 個項目。',
        'file' => ':attribute 必須大於 :value KB。',
        'numeric' => ':attribute 必須大於 :value。',
        'string' => ':attribute 必須超過 :value 個字元。',
    ],
    'gte' => [
        'array' => ':attribute 必須包含 :value 個以上項目。',
        'file' => ':attribute 必須大於或等於 :value KB。',
        'numeric' => ':attribute 必須大於或等於 :value。',
        'string' => ':attribute 必須大於或等於 :value 個字元。',
    ],
    'hex_color' => ':attribute 必須是有效的十六進位色碼。',
    'image' => ':attribute 必須是一張圖片。',
    'in' => '所選的 :attribute 無效。',
    'in_array' => ':attribute 必須存在於 :other 之中。',
    'in_array_keys' => ':attribute 必須包含以下至少一個鍵：:values。',
    'integer' => ':attribute 必須是整數。',
    'ip' => ':attribute 必須是有效的 IP 位址。',
    'ipv4' => ':attribute 必須是有效的 IPv4 位址。',
    'ipv6' => ':attribute 必須是有效的 IPv6 位址。',
    'json' => ':attribute 必須是有效的 JSON 字串。',
    'list' => ':attribute 必須是一個列表。',
    'lowercase' => ':attribute 必須是小寫。',
    'lt' => [
        'array' => ':attribute 必須包含少於 :value 個項目。',
        'file' => ':attribute 必須小於 :value KB。',
        'numeric' => ':attribute 必須小於 :value。',
        'string' => ':attribute 必須少於 :value 個字元。',
    ],
    'lte' => [
        'array' => ':attribute 不得包含超過 :value 個項目。',
        'file' => ':attribute 必須小於或等於 :value KB。',
        'numeric' => ':attribute 必須小於或等於 :value。',
        'string' => ':attribute 必須小於或等於 :value 個字元。',
    ],
    'mac_address' => ':attribute 必須是有效的 MAC 位址。',
    'max' => [
        'array' => ':attribute 不得超過 :max 個項目。',
        'file' => ':attribute 不得大於 :max KB。',
        'numeric' => ':attribute 不得大於 :max。',
        'string' => ':attribute 不得超過 :max 個字元。',
    ],
    'max_digits' => ':attribute 不得超過 :max 位數。',
    'mimes' => ':attribute 必須是以下檔案類型：:values。',
    'mimetypes' => ':attribute 必須是以下檔案類型：:values。',
    'min' => [
        'array' => ':attribute 至少需要 :min 個項目。',
        'file' => ':attribute 至少需要 :min KB。',
        'numeric' => ':attribute 不得小於 :min。',
        'string' => ':attribute 至少需要 :min 個字元。',
    ],
    'min_digits' => ':attribute 至少需要 :min 位數。',
    'missing' => ':attribute 必須不存在。',
    'missing_if' => '當 :other 為 :value 時，:attribute 必須不存在。',
    'missing_unless' => '除非 :other 為 :value，否則 :attribute 必須不存在。',
    'missing_with' => '當 :values 存在時，:attribute 必須不存在。',
    'missing_with_all' => '當 :values 都存在時，:attribute 必須不存在。',
    'multiple_of' => ':attribute 必須是 :value 的倍數。',
    'not_in' => '所選的 :attribute 無效。',
    'not_regex' => ':attribute 格式無效。',
    'numeric' => ':attribute 必須是數字。',
    'password' => [
        'letters' => ':attribute 必須包含至少一個字母。',
        'mixed' => ':attribute 必須包含至少一個大寫與一個小寫字母。',
        'numbers' => ':attribute 必須包含至少一個數字。',
        'symbols' => ':attribute 必須包含至少一個符號。',
        'uncompromised' => ':attribute 已出現在外洩資料中，請選擇其他 :attribute。',
    ],
    'present' => ':attribute 必須存在。',
    'present_if' => '當 :other 為 :value 時，:attribute 必須存在。',
    'present_unless' => '除非 :other 為 :value，否則 :attribute 必須存在。',
    'present_with' => '當 :values 存在時，:attribute 必須存在。',
    'present_with_all' => '當 :values 都存在時，:attribute 必須存在。',
    'prohibited' => ':attribute 被禁止使用。',
    'prohibited_if' => '當 :other 為 :value 時，:attribute 被禁止使用。',
    'prohibited_if_accepted' => '當 :other 被接受時，:attribute 被禁止使用。',
    'prohibited_if_declined' => '當 :other 被拒絕時，:attribute 被禁止使用。',
    'prohibited_unless' => '除非 :other 在 :values 之中，否則 :attribute 被禁止使用。',
    'prohibits' => ':attribute 禁止 :other 存在。',
    'regex' => ':attribute 格式無效。',
    'required' => ':attribute 為必填欄位。',
    'required_array_keys' => ':attribute 必須包含以下項目：:values。',
    'required_if' => '當 :other 為 :value 時，:attribute 為必填。',
    'required_if_accepted' => '當 :other 被接受時，:attribute 為必填。',
    'required_if_declined' => '當 :other 被拒絕時，:attribute 為必填。',
    'required_unless' => '除非 :other 在 :values 之中，否則 :attribute 為必填。',
    'required_with' => '當 :values 存在時，:attribute 為必填。',
    'required_with_all' => '當 :values 都存在時，:attribute 為必填。',
    'required_without' => '當 :values 不存在時，:attribute 為必填。',
    'required_without_all' => '當 :values 都不存在時，:attribute 為必填。',
    'same' => ':attribute 必須與 :other 相符。',
    'size' => [
        'array' => ':attribute 必須包含 :size 個項目。',
        'file' => ':attribute 必須是 :size KB。',
        'numeric' => ':attribute 必須是 :size。',
        'string' => ':attribute 必須是 :size 個字元。',
    ],
    'starts_with' => ':attribute 必須以以下任一值開頭：:values。',
    'string' => ':attribute 必須是字串。',
    'timezone' => ':attribute 必須是有效的時區。',
    'unique' => ':attribute 已被使用。',
    'uploaded' => ':attribute 上傳失敗。',
    'uppercase' => ':attribute 必須是大寫。',
    'url' => ':attribute 必須是有效的 URL。',
    'ulid' => ':attribute 必須是有效的 ULID。',
    'uuid' => ':attribute 必須是有效的 UUID。',

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
            'accepted' => '您必須勾選確認已告知客戶並取得簽名。',
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
        'username' => '帳號',
        'name' => '姓名',
        'email' => '電子郵件',
        'password' => '密碼',
        'current_password' => '目前密碼',
        'password_confirmation' => '確認密碼',
        'phone' => '電話',
        'machine_id' => '機台',
        'category' => '類別',
        'maintenance_at' => '維修日期',
        'content' => '維修內容',
        'is_confirmed' => '確認勾選框',
    ],

];
