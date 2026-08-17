<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait TenantScoped
{
    /**
     * Boot the trait.
     */
    public static function bootTenantScoped(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            // 避免在 User Model 本身套用此 Scope，否則在 auth()->user() 讀取 User 時會產生循環引用
            if (static::class === \App\Models\System\User::class) {
                return;
            }

            // check if running in console/migration (but not during unit tests)
            if (app()->runningInConsole() && !app()->runningUnitTests()) {
                return;
            }

            $user = auth()->user();
            
            // 如果使用者已登入且有綁定公司，則自動注入過濾條件
            if ($user && $user->company_id) {
                $query->where((new static)->getTable() . '.company_id', $user->company_id);
            }
        });

        // 建立資料時，自動填入當前使用者的 company_id
        static::creating(function ($model) {
            if (!$model->company_id) {
                $user = auth()->user();
                if ($user && $user->company_id) {
                    $model->company_id = $user->company_id;
                }
            }
        });
    }

    /**
     * Define the company relationship.
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\System\Company::class);
    }
}
