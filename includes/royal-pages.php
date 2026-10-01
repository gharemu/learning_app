<?php
function royal_hero(string $heading,string $sub): string {
 return '<header class="rp-hero"><div><h1>'.e($heading).'</h1><p>'.e($sub).'</p></div><blockquote lang="sa">विद्या ददाति विनयं,<br>विनयाद् याति पात्रताम्।<br>पात्रत्वाद्धनमाप्नोति,<br>धनाद्धर्मं ततः सुखम्॥</blockquote></header>';
}
function royal_explorer(PDO $pdo,string $system): string {
 $names=['gurukul'=>'Gurukul Education System','indian'=>'Current Education System India','nepal'=>'Current Education System Nepal'];
 $categories=dbrows($pdo,'SELECT * FROM categories WHERE system=? AND parent_id IS NULL ORDER BY position,title',[$system]);
 $selected=(string)($_GET['department']??'');$category=null;foreach($categories as $cat)if($cat['id']===$selected)$category=$cat;
 $filters=$category?jsonval($category['filters']):[];
 // The original curriculum contains subject filters, including dependencies on class/board.
 $where=['c.published=1','cat.system=?'];$args=[$system];
 if($category){$where[]='c.category_id=?';$args[]=$selected;}
 foreach($filters as $filter){$key=$filter['key']??'';if(!preg_match('/^[a-zA-Z_]+$/',$key))continue;$value=trim((string)($_GET[$key]??''));if($value!==''){$where[]="JSON_UNQUOTE(JSON_EXTRACT(c.filters, '$.".$key."'))=?";$args[]=$value;}}
 $query=trim((string)($_GET['q']??''));if($query!==''){$where[]='(c.title LIKE ? OR c.description LIKE ?)';$args[]='%'.$query.'%';$args[]='%'.$query.'%';}
 $rows=dbrows($pdo,'SELECT c.*,(SELECT COUNT(*) FROM chapters ch WHERE ch.course_id=c.id) modules,(SELECT COUNT(*) FROM lessons l JOIN chapters ch ON ch.id=l.chapter_id WHERE ch.course_id=c.id) lessons FROM courses c JOIN categories cat ON cat.id=c.category_id WHERE '.implode(' AND ',$where).' ORDER BY c.title LIMIT 250',$args);
 $course=null;foreach($rows as $r)if($r['id']===($_GET['course']??''))$course=$r;
 $url='/'.$system;$o='<main class="rp-page royal-explorer">'.royal_hero($names[$system],'Discover your department, choose a subject, and begin learning.').'<div class="royal-steps"><span>Departments</span><span>Subjects</span><span>Courses</span><span>Modules</span><span>Lessons</span></div><div class="royal-explorer-grid"><aside class="rp-panel"><h2>Departments</h2>';
 foreach($categories as $cat)$o.='<a class="royal-list-link '.($selected===$cat['id']?'selected':'').'" href="'.$url.'?department='.urlid($cat['id']).'">'.ico('book').'<span>'.e($cat['title']).'</span> ›</a>';
 $o.='</aside><section class="rp-panel"><h2>Subjects & Courses</h2><form method="get" class="royal-filter"><input type="hidden" name="department" value="'.e($selected).'">';
 foreach($filters as $filter){$key=$filter['key']??'';$values=$filter['values']??[];$dep=$filter['dependsOn']??'';if($dep && isset($filter['optionsBy'][$_GET[$dep]??'']))$values=$filter['optionsBy'][$_GET[$dep]];
 $o.='<label>'.e($filter['label']??$key).'<select name="'.e($key).'"><option value="">Choose '.e($filter['label']??$key).'</option>';foreach($values as $v)if(is_scalar($v))$o.='<option value="'.e($v).'"'.(($_GET[$key]??'')===$v?' selected':'').'>'.e($v).'</option>';$o.='</select></label>';}
 $o.='<label>Search courses<input name="q" value="'.e($query).'" placeholder="Course or topic"></label><button class="button">Show courses</button></form>';
 if(!$category)$o.='<p class="notice">Choose a department to view its subject and class filters.</p>';
 foreach($rows as $r){$q=$_GET;$q['course']=$r['id'];$o.='<a class="royal-list-link '.(($course['id']??'')===$r['id']?'selected':'').'" href="'.$url.'?'.e(http_build_query($q)).'"><span><b>'.e($r['title']).'</b><small>'.(int)$r['modules'].' Modules · '.(int)$r['lessons'].' Lessons</small></span> ›</a>';}
 if(!$rows)$o.='<p>No published courses match these selections yet. The academy can add lessons through the course builder.</p>';
 $o.='</section><aside class="rp-panel royal-preview"><h2>Course Details</h2>';
 if($course){$o.='<img src="'.e($course['image']?:'/images/course-vedic-study.png').'" alt="'.e($course['title']).'"><h2>'.e($course['title']).'</h2><p>'.(int)$course['modules'].' Modules | '.(int)$course['lessons'].' Lessons</p><p>'.e($course['description']).'</p><a class="ag-gold-button" href="/course/'.urlid($course['id']).'">View Modules & Lessons →</a>';}else $o.='<div class="royal-preview-empty">'.ico('book',64).'<h3>Your next chapter awaits</h3><p>Select a course to see its image, syllabus and learning resources.</p></div>';
 return $o.'</aside></div></main>';
}
function royal_plans(): string {
 $o='<main class="page royal-membership">'.royal_hero('One membership. A world of learning.','Learn across Gurukul, India and Nepal education systems.').'<div class="royal-plan-grid">';
 foreach([['Monthly',500,'one month'],['Yearly',3999,'one year']] as [$plan,$price,$term])$o.='<section class="rp-panel"><span class="eyebrow">ALL-COURSE ACCESS</span><h2>'.$plan.' Membership</h2><strong class="royal-price">₹'.number_format($price).'</strong><p>Access for '.$term.'</p><ul><li>All published courses</li><li>Available video lectures and resources</li><li>Quizzes, assignments and progress tracking</li></ul><a class="ag-gold-button" href="https://rzp.io/rzp/jalgaonmh" target="_blank" rel="noopener">Pay on Razorpay ↗</a><p class="notice">Pay the selected plan amount using your registered email. Keep your payment ID. Academy verification is required before membership is activated.</p></section>';
 return $o.'</div><p>No automatic renewal is enabled. Payment-link completion alone does not activate access.</p><a class="button" href="/dashboard">My learning account →</a></main>';
}
function royal_login_links(): string {return '<nav class="royal-portals" aria-label="Academy portals"><a href="/login">Student Login</a><a href="/teacher/login">Teacher Login</a><a href="/creator/login">Course Creator Login</a><a href="/review/login">Course Approval Login</a><a href="/timetable/login">Time Table Login</a><a href="/accounts/login">Accounts Login</a><a href="/super-admin/login">Super Admin Login</a><a href="/signup">Student Registration</a><a href="/creator/signup">Course Creator Registration</a><a href="/plans">Membership Plans</a></nav>';}
function royal_render(PDO $pdo,string $path,?array $user,?string &$portalMode,string &$title,string &$content): void {
 if(in_array($path,['gurukul','indian','nepal'],true)){$content=royal_explorer($pdo,$path);$portalMode='student';http_response_code(200);}
 if($path==='plans'){$content=royal_plans();$title='Membership Plans';$portalMode=null;http_response_code(200);}
 if(in_array($path,['login','teacher/login','creator/login','creator/signup','signup','review/login','timetable/login','accounts/login','super-admin/login','admin/login'],true)){
  $settings=google_settings();$google=!empty($settings['google_client_id'])?'<a class="royal-google" href="/auth/google"><b>G</b> Continue with Google</a><small>Existing accounts: sign in with your password once, then link Google from your profile.</small>':'';
  $content=str_replace('</section></main>',$google.'</section></main>',$content);
 }
 if($path==='profile'&&$user)$content=str_replace('</main>','<section class="rp-panel"><h2>Google Sign-in</h2><p>Link the Google account with the same email as your academy account.</p><a class="button" href="/auth/google">Link my Google account</a></section></main>',$content);
 if($path==='dashboard')$content=str_replace('</main>','<section class="rp-panel"><h2>All-Course Membership</h2><p>Monthly ₹500 · Yearly ₹3,999</p><a class="button" href="/plans">View membership plans</a></section></main>',$content);
}
