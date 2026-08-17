<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * 地址地理編碼代理控制器
 * 
 * 透過後端轉發 Nominatim API 請求，解決瀏覽器無法自訂 User-Agent
 * 導致 Nominatim 回傳 403 Forbidden 的問題。
 */
class GeocodingController extends Controller
{
    /**
     * 將台灣地址轉換為經緯度座標
     */
    public function resolve(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:255',
        ]);

        $address = $request->input('address');

        // 1. 清洗台灣地址中的樓層、室、地下室與括號備註，避免干擾地理編碼解析
        $address = $this->cleanFloorInfo($address);

        // 策略 1：結構化搜尋（台灣地址精確度最高）
        $parsed = $this->parseTaiwanAddress($address);

        if ($parsed['street']) {
            $response = Http::withHeaders([
                'User-Agent' => 'StarCloud/1.0 (admin@starcloud.com)',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'json',
                'street' => $parsed['street'],
                'city'   => $parsed['city'],
                'country' => 'Taiwan',
                'limit'  => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data)) {
                    return response()->json([
                        'success' => true,
                        'lat' => (float) $data[0]['lat'],
                        'lon' => (float) $data[0]['lon'],
                        'display_name' => $data[0]['display_name'] ?? '',
                    ]);
                }
            }
        }

        // 策略 2：自由搜尋 + 台灣限定
        $searchQuery = $address;
        if (!str_contains($searchQuery, '台灣') && !str_contains($searchQuery, 'Taiwan')) {
            $searchQuery .= ', 台灣';
        }

        $response = Http::withHeaders([
            'User-Agent' => 'StarCloud/1.0 (admin@starcloud.com)',
        ])->get('https://nominatim.openstreetmap.org/search', [
            'format' => 'json',
            'q'      => $searchQuery,
            'countrycodes' => 'tw',
            'limit'  => 1,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (!empty($data)) {
                return response()->json([
                    'success' => true,
                    'lat' => (float) $data[0]['lat'],
                    'lon' => (float) $data[0]['lon'],
                    'display_name' => $data[0]['display_name'] ?? '',
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => __('Location not found'),
        ], 404);
    }

    /**
     * 解析台灣地址格式為結構化參數
     * 例如：「台中市北區興進路90之3號」→ city: "台中市 北區", street: "90之3 興進路"
     */
    private function parseTaiwanAddress(string $address): array
    {
        $address = trim($address);

        // 解析城市 (市/縣)
        preg_match('/^(.+?[市縣])/u', $address, $cityMatch);
        $city = $cityMatch[1] ?? '';

        // 解析區域 (區/鄉/鎮) — 排除「市」以避免與城市混淆
        preg_match('/[市縣](.+?[區鄉鎮])/u', $address, $districtMatch);
        $district = $districtMatch[1] ?? '';

        // 取得路街部分：從最後一個「區/鄉/鎮」之後開始截取
        if ($district) {
            // 精確定位到 district 結束後的部分
            $pos = mb_strpos($address, $district);
            if ($pos !== false) {
                $streetPart = mb_substr($address, $pos + mb_strlen($district));
            } else {
                $streetPart = $address;
            }
        } else {
            $streetPart = $address;
        }

        // 拆分路名與門牌號碼（層級感知台灣地址結構，完整保留巷弄）
        preg_match('/^(.+?(?:路|街|大道)(?:[一二三四五六七八九十]+段)?(?:[0-9一二三四五六七八九十百]+巷)?(?:[0-9一二三四五六七八九十百]+弄)?)\s*(.*)/u', $streetPart, $roadMatch);

        $street = '';
        $houseNumber = '';
        if ($roadMatch) {
            $street = $roadMatch[1];
            $houseNumber = str_replace('號', '', $roadMatch[2] ?? '');
        } else {
            $street = $streetPart;
        }

        $fullStreet = ($houseNumber ? $houseNumber . ' ' : '') . $street;
        $fullCity = $city . ($district ? ' ' . $district : '');

        return [
            'city' => $fullCity,
            'street' => $fullStreet,
        ];
    }

    /**
     * 清洗台灣地址中的樓層、室、地下室與括號備註，避免干擾 OpenStreetMap 解析
     */
    private function cleanFloorInfo(string $address): string
    {
        // 1. 移除常見的樓層與地下室資訊，以及其後方的所有贅字：例如 "2樓", "12樓之3", "89F", "B1", "地下1樓" 等
        $address = preg_replace('/(?:地下一?|地下)?(?:[0-9一二三四五六七八九十百]+樓|[0-9a-zA-Z]+[fF]|[bB][0-9]+)(?:之[0-9一二三四五六七八九十百]+)?.*/u', '', $address);
        
        // 2. 移除 "x室", "x房", "x戶" 等室內資訊
        $address = preg_replace('/(?:[0-9a-zA-Z一二三四五六七八九十百]+(?:室|房|戶)).*/u', '', $address);

        // 3. 移除常見 the 括號備註，例如 "(五樓)", "(A棟)", "(總統府)"
        $address = preg_replace('/[\(（][^\)）]+[\)）]/u', '', $address);

        return trim($address);
    }
}
