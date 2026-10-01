<?php
// Run after importing schema.sql: php database/seed.php
require dirname(__DIR__) . '/includes/bootstrap.php';
function source_cover(array $course): string {
    $featured = ['g-015'=>'course-bhagavad-gita.png','g-046'=>'course-yagya.png','g-021'=>'course-astrology.png','g-043'=>'course-meditation.png','g-122'=>'course-robotics.png','g-212'=>'course-tourism.png'];
    $departmentImages = ['ved.webp','self.webp','science.webp','techno.webp','ai.webp','robot.webp','it.webp','space.webp','techno.webp'];
    $title = mb_strtolower($course['title'] ?? ''); $group = $course['group'] ?? ''; $file=$featured[$course['id']]??null;
    if (!$file && preg_match('/mathematics|linear equations/u',$title)) $file='course-mathematics.png';
    if (!$file && (int)($course['department']??-1)===0) {
        $file=str_starts_with($group,'1.4')?'course-astrology.png':(str_starts_with($group,'1.5')?'course-tourism.png':(str_starts_with($group,'1.6')?'ayurveda.png':(str_starts_with($group,'1.7')?'course-meditation.png':'course-vedic-study.png')));
    }
    if (!$file && (int)($course['department']??-1)===8) {
        if (preg_match('/herbal|wellness/u',$title)) $file='ayurveda.png';
        elseif (preg_match('/panchgavya|dairy farming/u',$title)) $file='gaushala.png';
        elseif (preg_match('/solar|green energy|ev /u',$title)) $file='energy.png';
        elseif (str_contains($title,'fitness')) $file='sports.png';
        elseif (str_starts_with($group,'B.')||str_starts_with($group,'C.')) $file='home-science.png';
        elseif (str_starts_with($group,'G.')) $file='commerce.png';
        elseif (str_starts_with($group,'I.')) $file='journalism.png';
        elseif (preg_match('/chemical|adhesive|lubricant|paint/u',$title)) $file='science-college.png';
        elseif (str_starts_with($group,'D.')||str_starts_with($group,'A.')) $file='engineering.png';
    }
    return '/images/'.($file??($departmentImages[(int)($course['department']??-1)]??'science.webp'));
}
$pdo->beginTransaction();
try {
    $categories = json_decode(file_get_contents(__DIR__ . '/categories.json'), true, 512, JSON_THROW_ON_ERROR);
    $cat = $pdo->prepare('INSERT IGNORE INTO categories(id,parent_id,system,title,image,position,filters) VALUES(?,?,?,?,?,?,?)');
    foreach ($categories as $c) $cat->execute([$c['id'],$c['parent_id'] ?? null,$c['system'],$c['title'],$c['image'] ?? '',$c['position'] ?? 0,json_encode($c['filters'] ?? [], JSON_UNESCAPED_UNICODE)]);
    $catalog = json_decode(file_get_contents(__DIR__ . '/legacy-catalog.json'), true, 512, JSON_THROW_ON_ERROR);
    $courseQ = $pdo->prepare('INSERT IGNORE INTO courses(id,category_id,title,description,image,instructor,filters,featured) VALUES(?,?,?,?,?,?,?,?)');
    $chapterQ = $pdo->prepare('INSERT IGNORE INTO chapters(id,course_id,title,position) VALUES(?,?,?,?)');
    $lessonQ = $pdo->prepare('INSERT IGNORE INTO lessons(id,chapter_id,title,body,hindi,english,video_url,pdf_url,ppt_url,source_url,duration,position,is_free) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $questionQ = $pdo->prepare('INSERT INTO questions(lesson_id,prompt,options,answer,explanation) VALUES(?,?,?,?,?)');
    foreach ($catalog['courses'] as $c) {
        $category = $c['system'] === 'gurukul' ? 'gurukul-' . $c['department'] : 'cbse';
        $image = source_cover($c);
        $courseQ->execute([$c['id'],$category,$c['title'],$c['description'] ?? 'Structured learning resources for this subject.',$image,'Ashwamedh Gurukul',json_encode(['group'=>$c['group'] ?? ''],JSON_UNESCAPED_UNICODE),count($c['chapters'] ?? []) > 0]);
        if (!$courseQ->rowCount()) continue;
        foreach (($c['chapters'] ?? []) as $i => $ch) {
            $id = $c['id'] . '--' . $ch['id'];
            $chapterQ->execute([$id,$c['id'],$ch['title'],$i]);
            $media = fn($v) => str_starts_with((string)$v,'assets/') ? '/samples/' . substr($v,7) : (string)$v;
            $lessonQ->execute([$id,$id,$ch['title'],$ch['body'] ?? '',$ch['hindi'] ?? '',$ch['english'] ?? '',$media($ch['video'] ?? ''),$media($ch['pdf'] ?? ''),$media($ch['ppt'] ?? ''),$ch['source'] ?? '',str_starts_with((string)($ch['video'] ?? ''),'assets/') ? 48 : 0,$i,!empty($ch['free'])]);
            foreach (($ch['questions'] ?? []) as $q) $questionQ->execute([$id,$q['prompt'],json_encode($q['options'],JSON_UNESCAPED_UNICODE),$q['answer'],$q['explanation'] ?? '']);
        }
    }
    $pdo->commit();
    echo 'Catalog import complete: ' . count($catalog['courses']) . " courses processed.\n";
} catch (Throwable $e) { $pdo->rollBack(); throw $e; }
