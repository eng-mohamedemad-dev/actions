<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http; 
use Symfony\Component\DomCrawler\Crawler;

Route::get('/scrape', function () {
    $baseUrl = 'https://www.yallakora.com';
    $html = Http::get($baseUrl)->body();

    // 2. نعمل Crawler للـ HTML
    $crawler = new Crawler($html);

    // 3. نمسك الصفوف بتاعة الجدول
    $items = $crawler->filter('section div.cnts ul li')->each(function (Crawler $node) use ($baseUrl) {
        $title = '';
        $link = '';
        $img = '';
        $desc = '';
        $time = '';

        // الرابط والعنوان والصورة من div.link > a
        if ($node->filter('div.link > a')->count()) {
            $aTag = $node->filter('div.link > a')->first();
            $link = $aTag->attr('href');
            // إضافة الرابط الأساسي إذا كان الرابط نسبي
            if ($link && strpos($link, 'http') !== 0) {
                $link = $baseUrl . $link;
            }
            
            if (trim($aTag->text())) {
                $title = trim($aTag->text());
            } elseif ($aTag->filter('img')->count()) {
                $title = $aTag->filter('img')->attr('alt');
            }
            
            if ($aTag->filter('img')->count()) {
                $img = $aTag->filter('img')->attr('src');
            }
        }
        
        // بحث عن أي صورة في كل العناصر إذا لم نجد من قبل
        if (!$img && $node->filter('img')->count()) {
            $img = $node->filter('img')->first()->attr('src');
        }

        // إذا لم يوجد رابط، جرب أي a داخل العنصر
        if (!$link && $node->filter('a')->count()) {
            $link = $node->filter('a')->first()->attr('href');
            // إضافة الرابط الأساسي إذا كان الرابط نسبي
            if ($link && strpos($link, 'http') !== 0) {
                $link = $baseUrl . $link;
            }
        }

        // الوصف
        if ($node->filter('div.desc')->count()) {
            $desc = trim($node->filter('div.desc')->text());
            
            // إذا لم يوجد عنوان، جرب استخراجه من الوصف
            if (!$title && $desc) {
                // أولاً: نبحث عن نص بين علامتي اقتباس
                if (preg_match('/^"(.+?)"/u', $desc, $matches)) {
                    $title = $matches[1];
                } 
                // ثانياً: نستخرج النص قبل التاريخ
                elseif (preg_match('/^(.+?)(?=\d{1,2} \p{Arabic}+ \d{4})/u', $desc, $matches)) {
                    $title = trim($matches[1]);
                }
                // ثالثاً: نستخرج النص حتى أول نقطة أو شرطتين متتاليتين
                elseif (preg_match('/^(.+?)(?=\.|\.\.|…|--)/u', $desc, $matches)) {
                    $title = trim($matches[1]);
                }
                // رابعاً: نأخذ أول 100 حرف
                else {
                    $title = mb_substr($desc, 0, 100, 'UTF-8');
                    if (mb_strlen($desc, 'UTF-8') > 100) {
                        $title .= '...';
                    }
                }
            }
        }

        // الوقت
        if ($node->filter('div.time.icon-time')->count()) {
            $time = trim($node->filter('div.time.icon-time')->text());
        } 
        // إذا لم يجد الوقت، يحاول استخراجه من نهاية الوصف
        elseif ($desc && !$time) {
            if (preg_match('/(\d{1,2} \p{Arabic}+ \d{4} \d{1,2}:\d{2} [أص|م]{1})/u', $desc, $matches)) {
                $time = $matches[1];
            }
        }

        // التأكد من أن الروابط كاملة
        if ($img && strpos($img, 'http') !== 0) {
            // في حالة الصور المطلقة التي تبدأ بـ /
            if (strpos($img, '/') === 0) {
                $img = $baseUrl . $img;
            } 
            // في حالة الصور التي تبدأ بـ \\
            elseif (strpos($img, '\\') === 0 || strpos($img, '//') === 0) {
                $img = 'https:' . str_replace('\\', '/', $img);
            }
        }
        
        return [
            'title' => $title,
            'link' => $link,
            'img' => $img,
            'desc' => $desc,
            'time' => $time,
        ];
    });
    
    return $items;
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('test_actions',function() {
    return 'action';
});

Route::get('test after push in steup and now we will push in deploy',function() {
    return 'action from test after push in steup and now we will push in deploy';
});