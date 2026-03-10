<?php
function vs_normalize(string $text): string {
    return mb_strtolower(trim($text), 'UTF-8');
}

function vs_detect_keywords(string $text, array $weights): array {
    $normalized = vs_normalize($text);
    $found = [];

    foreach ($weights as $word => $weight) {
        $needle = vs_normalize((string)$word);
        if ($needle === '') continue;

        if (mb_stripos($normalized, $needle, 0, 'UTF-8') !== false) {
            $found[] = (string)$word;
        }
    }

    return array_values(array_unique($found));
}

function vs_calc_risk_from_found(array $found_keywords, array $weights): float {
    if (empty($found_keywords)) return 0;

    $total_weight = 0;
    foreach ($found_keywords as $w) {
        $total_weight += (int)($weights[$w] ?? 2);
    }

    $max_weight = max($weights);        
    $max_score_ideal = $max_weight * 5;   

    $base_ratio = min($total_weight / $max_score_ideal, 1.0);
    $base_risk  = $base_ratio * 100;

    $density_boost = min(count($found_keywords), 10) * 1.5;

    return round(min(100, $base_risk + $density_boost), 1);
}

function vs_boost_high_triggers(string $text, float $risk): float {
    $highTriggers = [
        "تفجير","انفجار","خطف","قتل","سلاح","تهديد","ابتزاز","حريق",
        "knife","gun","bomb","kidnap","explosion","fire","murder","threat","extortion"
    ];

    $normalized = vs_normalize($text);

    foreach ($highTriggers as $t) {
        if (mb_stripos($normalized, vs_normalize($t), 0, 'UTF-8') !== false) {
            return max($risk, 85);
        }
    }
    return $risk;
}

function vs_category_and_actions(float $risk): array {
    if ($risk >= 75) {
        return [
            "category" => "High Priority",
            "actions"  => "🚨 تصعيد فوري: مؤشرات خطورة قوية. خذي الموقع/هوية المتصل بسرعة، وتجنّبي أي رموز/بيانات حساسة."
        ];
    }
    if ($risk >= 40) {
        return [
            "category" => "Needs Review",
            "actions"  => "⚠️ يحتاج متابعة: اطلب/ي تفاصيل إضافية (الموقع، الوقت، نوع البلاغ) وتحقّق من أي مؤشرات احتيال."
        ];
    }
    return [
        "category" => "Normal",
        "actions"  => "✅ بلاغ طبيعي: لا توجد مؤشرات خطر قوية حتى الآن."
    ];
}

function vs_analyze_text(string $text, array $weights): array {
    $found = vs_detect_keywords($text, $weights);
    $risk  = vs_calc_risk_from_found($found, $weights);
    $risk  = vs_boost_high_triggers($text, $risk);
    $meta  = vs_category_and_actions($risk);

    return [
        "text"     => $text,
        "keywords" => $found,
        "risk"     => $risk,
        "category" => $meta["category"],
        "actions"  => $meta["actions"],
    ];
}
