<?php
// =======================================================
// 	SPECLATIVE - AN INTERNET LAXATIVE
//  DATE: 7/12/2009
//  URL: http://www.toggle.uk.com
// =======================================================

// include SPF
include('includes/master.inc.php');

// page generation time start
$start = round(microtime(), 4); 
    
// turn error reporting on or off
ini_set('display_errors', '0');

// check if we are querying a URL
if (isset($_GET['q'])) {

	// our words and sentances
	$super_good = array('confirm','confirmed','proof','proven','fact','factual','no doubt','speclative','pository'); //'is', 'has', 'was'
	$good = array('laxative','indicates','has announced','occurred','launches','launched','said','saying','revealed','which','it emerged','is poised','concluded','conclusion','witnessed','witness','reported','report','been','emerged','found','statement','stated','identified','research','evidence','evident','proof','proven','begun','failed','clear','clearly','has killed','there were','officials say','has','showed'); //'will','never','have'
	$bad = array('dodgy','plans to','thought to be','thought to have','thought','said to have','believed to be','believed','according to','which expects','according to sources','sources','source','hoped','hoping','would have','seems','suspected','suspects','potentially','potential','fears about','feared','fear','aims','heard','lead','theory','sources claimed','claim','claiming','claimed','expected','suggestions','suggest','suggestion','insiders','perhaps','appear','indicated','critics','critic','newspaper','statistics','per cent','survey','poll','percent','may','critics','perceived','estimate'); // 'the sun','daily mail' 
	$super_bad = array('alleging','alleged','allegedly','assumption','assumptions','assumed','could be','could have','could well','possibly','possibility','apparently','might','rumour','rumours','speculated','speculate','speculates','speculation'); // 'if'

	// increase memory limit - this is an intensive process
	ini_set("memory_limit","40M");

	// if the URL is not valid return an error or redirect
	if (!validate_url(urldecode($_GET['q']))) { print "ERROR: Invalid URL. We could not validate this URL - (1)."; exit(); } 
	if (!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', urldecode($_GET['q']))) {echo "ERROR: Invalid URL. We could not validate this URL - (2)."; exit(); } 	

	// sanitize the URL 
	$url = filter_var(urldecode($_GET['q']), FILTER_SANITIZE_URL);
	
	// fix slashes
	$url = unslash($url);
	
	// check for a matching URL in the database if found return score else create score
	$s = new Score();
	if ($s->select($url, 'url')) {
		
		// grab the updated time
		$updated	= strtotime($s->updated);
		
		// grab the number of views
		$views 		= intval($s->count);

		// if the link was last updated over 3 hours ago then re-filter it (pages sometimes change - e.g. BBC homepage)
		if ((time() - $updated) >= 10800) {
		
			// load the simple dom class
			$html = new simple_html_dom($url);
			
			// grab the page title for saving to the database
			$title = filter_var($html->find('title', 0), FILTER_SANITIZE_STRING);
					
			// get the contents of the url as plain text
			$html = $html->plaintext;
			
			// remove whitespace in html
			$html = preg_replace('/\s\s+/', ' ', $html);
			
			// if no html is returned then it might have been a dead URL all along		
			if (!$html) { print "ERROR: Unable to retrieve HTML from this page - (3)."; exit(); }

			// get our counts
			$super_good_count = substr_count_array($html,$super_good);
			$good_count = substr_count_array($html,$good);
			$bad_count = substr_count_array($html,$bad);
			$super_bad_count = substr_count_array($html,$super_bad);
			
			// calculate scores
			$good_score = (($super_good_count*2)+($good_count));
			$bad_score = (($super_bad_count*2)+($bad_count));
			$score = ($good_score - $bad_score);
			
			// rank for quick DB search
			if (($good_score == 0) && ($bad_score == 0)) {
				$rank = 0;
			} else if ($score < 3) {
				$rank = 1;
			} else if (($score >= 3) && ($score <= 7)) {
				$rank = 2;
			} else {
				$rank = 3;
			}
			
			// set for updated information
			$s->ip  		= $_SERVER['REMOTE_ADDR'];
			$s->title		= preg_replace('/\s\s+/', ' ', $title);
			$s->goodscore  	= $good_score;
			$s->badscore  	= $bad_score;
			$s->score 		= $score;
			$s->rank		= $rank;
		
		// else grab out of the database
		} else {
				
			$good_score = intval($s->goodscore);
			$bad_score 	= intval($s->badscore);
			$score 		= intval($s->score);
			$rank 		= intval($s->rank); 
			$title		= $s->title;
		
		}
		
		// update changed info
		$s->count = $views+1;
		$s->updated = date('Y-m-d H:i:s');			
		$s->update();
		
	} else {
	
		// load the simple dom class
		$html = new simple_html_dom($url);
		
		// grab the page title for saving to the database
		$title = filter_var($html->find('title', 0), FILTER_SANITIZE_STRING);
				
		// get the contents of the url as plain text
		$html = $html->plaintext;
		
		// remove whitespace in html
		$html = preg_replace('/\s\s+/', ' ', $html);
		
		// if no html is returned then it might have been a dead URL all along		
		if (!$html) { print "ERROR: Unable to retrieve HTML from this page - (3)."; exit(); }
	
		// get our counts
		$super_good_count = substr_count_array($html,$super_good);
		$good_count = substr_count_array($html,$good);
		$bad_count = substr_count_array($html,$bad);
		$super_bad_count = substr_count_array($html,$super_bad);
		
		// calculate scores
		$good_score = (($super_good_count*2)+($good_count));
		$bad_score = (($super_bad_count*2)+($bad_count));
		$score = ($good_score - $bad_score);
		
		// rank for quick DB search
		if (($good_score == 0) && ($bad_score == 0)) {
			$rank = 0;
		} else if ($score < 3) {
			$rank = 1;
		} else if (($score >= 3) && ($score <= 7)) {
			$rank = 2;
		} else {
			$rank = 3;
		}
		
		// work out the domain name of URL
		$purl = parse_url($url);
			
		// insert score into database
		$s->time		= date('Y-m-d H:i:s');
		$s->ip  		= $_SERVER['REMOTE_ADDR'];
		$s->count		= 1;
		$s->url      	= $url;
		$s->title		= preg_replace('/\s\s+/', ' ', $title);
		$s->domain      = $purl['host'];
		$s->goodscore  	= $good_score;
		$s->badscore  	= $bad_score;
		$s->score 		= $score;
		$s->rank		= $rank;
		$s->insert();
	
	}
	
	// add to overall counter
	$c = new Counter();
	if ($c->select(1, 'id')) {
		$count = $c->count;
		$c->count = $count+1;
		$c->update();
	}
	
	if (($good_score == 0) && ($bad_score == 0)) {
	
		$output = array('score' => $score, 'rank' => $rank, 'feedback' => '<strong>Hmmmmm.</strong> We are unable to correctly diagnose this page, it may not contain many words.', 'icon' => 'icon-unknown.png');
	
	} else if ($rank == 1) {

		$output = array('score' => $score, 'rank' => $rank, 'feedback' => '<strong>Failed.</strong> This page is pure speculation. Perhaps your time would be better spent elsewhere.', 'icon' => 'icon-bad.png');
		
	} else if ($rank == 2) {
	
		$output = array('score' => $score, 'rank' => $rank, 'feedback' => '<strong>Neutral.</strong> We are unable to prescribe a recommendation for this page. Why not give it a read and send us <a href="http://speclative.scott.ee/#feedback" title="Speclative Feedback">your feedback</a>.', 'icon' => 'icon-neutral.png');
	
	} else if ($rank == 3) {
	
		$output = array('score' => $score, 'rank' => $rank, 'feedback' => '<strong>Passed.</strong> This page appears to be based on fact. Enjoy!', 'icon' => 'icon-good.png');
	
	}
	
	// output JS header
    header('Content-Type: application/x-javascript; charset=utf8');
    
	// return JSON for bookmarklet
	echo $_GET['callback'] . '(' . json_encode($output) . ');';
	
	// clean up
    unset($html);
    unset($url);
    unset($title);
    exit();

} else {

	ob_start();

?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>

	<!-- meta -->
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="revisit-after" content="7 days">
	<meta name="expires" content="never">
	<meta name="coverage" content="Worldwide">
	<meta name="distribution" content="Global">
	<meta name="identifier-url" content="http://speclative.scott.ee">
	<meta name="home_url" content="http://speclative.scott.ee">
	<meta name="company" content="Scott Evans">
	<meta name="author" content="Scott - scott.ee">
	<meta name="copyright" content="Scott - scott.ee">
	
	<!-- title -->
	<title>Speclative - The speculative news laxative (Bookmarklet)</title>
	<meta name="description" content="Are you fed up of the &lsquo;could be&rsquo;, &lsquo;possiblies&rsquo; and &lsquo;maybes&rsquo; found in todays news? Take back your time and sanity with Speclative &reg; : An Internet speculation filter (delivered via bookmarklet), that determines if an online article is worth reading (or not).">
	<meta name="keywords" content="speculation, speclative, news, filter, bookmarklet, laxative, medication, blogs, online, journalism, time, save, alleging, alleged, allegedly, assumption, assumptions, assumed, could be, could have, could well, possibly, possibility, apparently, might, rumour">
		
	<!-- favicon -->
	<link rel="shortcut icon" type="image/png" href="/favicon.png">
	
	<!-- css -->
	<link rel="stylesheet" type="text/css" href="/combine.php?type=css&amp;files=reset.css,screen.css">

	<!-- enable html5 in <= IE8 -->
	<!--[if lte IE 8]><script src="/js/html5.js"></script><![endif]-->
	
	<!-- <= IE7 css -->
	<!--[if lte IE 7]><link rel="stylesheet" type="text/css" href="/styles/ie.css"><![endif]-->

	<!-- flattr -->
	<script type="text/javascript">
	    (function() {
	        var s = document.createElement('script'), t = document.getElementsByTagName('script')[0];
	        
	        s.type = 'text/javascript';
	        s.async = true;
	        s.src = 'http://api.flattr.com/js/0.5.0/load.js?mode=auto';
	        
	        t.parentNode.insertBefore(s, t);
	    })();
	</script>
	
</head>
<body>
	<div id="container">
	
		<nav id="skip">
			<ul>
		    	<li><a href="#nav" title="Skip to the navigation &raquo;">Skip to the navigation &raquo;</a></li>
		        <li><a href="#content" title="Skip to the main content &raquo;">Skip to main content &raquo;</a></li>
		    </ul>
		</nav>
		<!-- #skip -->
	
		<section id="bookmarklet">
			<a href="javascript:(function(){speclativejs=document.createElement('SCRIPT');speclativejs.type='text/javascript';speclativejs.src='http://speclative.scott.ee/js/speclative.js';document.getElementsByTagName('head')[0].appendChild(speclativejs);})();" title="Speclative" class="replace" id="bookmarklet-pill">Speclative<span></span></a>
			<p>Drag the pill to your bookmarks toolbar</p>
		</section>
		<!-- #bookmarklet -->
		
		<div id="content-container">
			
			<section id="content">
			
				<header id="header">
					<h1><a href="http://speclative.scott.ee/" title="Speclative - The speculative news laxative" id="logo" class="replace">Speclative<span></span></a></h1>
					<h2>The speculative news laxative</h2>
					<p id="introduction" class="replace">Are you fed up of the &lsquo;could be&rsquo;, &lsquo;possiblies&rsquo; and &lsquo;maybes&rsquo; found in todays news? Take back your time and sanity with Speclative &reg; : An Internet speculation filter (delivered via bookmarklet), that determines if an online article is worth reading (or not).<span></span></p>
				</header>
				<!-- header -->
				
				<!-- slider begin -->
				<div id="slider">
					<div id="slider-container">
					
						<article id="home" class="panel">
							<div id="label">
								<p>Prescribed to: <?php print $_SERVER['REMOTE_ADDR'];?>.</p>
								<p>Take as required. Take orally.</p>
								<p>Consult your GP (<a href="#feedback" title="Speclative Feedback">Scott Evans</a>) if problems persist.</p>
							</div>
							<!-- #label -->
							<span id="home-capsules" class="home-type">28 Capsules</span>
							<span id="home-dose" class="home-type">10mg</span>
						</article>
						<!-- #home -->
						
						<article id="help" class="panel">
							<h3>Help</h3>
							<h4>What is a bookmarklet?</h4>
							<p>A bookmarklet is a small JavaScript application stored as a bookmark in your web browser. You can find out more about bookmarklets on <a href="http://en.wikipedia.org/wiki/Bookmarklet" title="Bookmarklet">Wikipedia</a>. </p>
							<h4>How do I install the Speclative bookmarklet?</h4>
							<p>You need to drag the pink pill at the top of the screen into your browsers bookmark toolbar. <a href="http://www.youtube.com/watch?v=QrwevUN0KdQ" title="Install a Bookmarklet">Google have produced a video</a> detailing this process.</p>
							<h4>How do I use it?</h4>
							<p>Visit a web page and click the Speclative bookmarklet. We will analyse the page and send back our feedback. You will instantly know whether or not the site/article is worth reading.</p>
							<h4>Do you track my browsing habits?</h4>
							<p>Nope, we do not track your browsing habits. Pages are analysed anonymously.</p>
						</article>
						<!-- #help -->
						
						<article id="trends" class="panel">
							<h3>Trends</h3>
							<h4>Coming soon&hellip;</h4>
							<p>As this is a new service we are still busy collecting data. Once we have enough we will launch a real-time trends visualisation. We will be tracking things like:</p>
							<ul>
								<li>Best and worst news stories for speculative news.</li>
								<li>Best and worst domains. Perhaps one new source is particularly unreliable?</li>
								<li>Popular news stories.</li>
								<li>Real-time information for sites tested (those passing and failing).</li>
							</ul>
							<p>Watch this space.</p>
						</article>
						<!-- #trends -->
						
						<article id="feedback" class="panel">
							<h3>Feedback</h3>
							<h4>Suggestions? Bugs?</h4>
							<p>Let me know how you&acute;re getting on. You can find me on twitter (<a href="http://twitter.com/scottsweb" title="Scott Evans on twitter">@scottsweb</a>) or ask me a question via my website: <a href="http://scott.ee" title="Scott Evans - Digital Designer">scott.ee</a>. Please supply the URL and score of the site tested when reporting bugs or inaccuracies.</p>
						</article>
						<!-- #feedback -->
					</div>					
					<!-- #slider-container -->
				</div>
				<!-- #slider -->	
			</section>
			<!-- #content -->
		</div>
		<!-- #content-container -->
		
		<footer id="footer">
			<nav id="nav">
				<ul>
					<li><a href="#home" class="selected">Home</a></li>
					<li><a href="#help">Help</a></li>
					<li><a href="#trends">Trends</a></li>
					<li><a href="#feedback">Feedback</a></li>
					<li id="flattr"><a class="FlattrButton" style="display:none;" rev="flattr;button:compact;" href="http://speclative.scott.ee/"></a></li>
				</ul>
			</nav>
			<!-- #nav -->
			
			<ul id="social">
				<li><a href="http://www.myspace.com/Modules/PostTo/Pages/?u=<?php print urlencode(full_url());?>&amp;t=<?php print urlencode("Speclative - The speculative news laxative");?>" id="myspace" class="replace social-media" title="Share on MySpace">Share on MySpace<span></span></a></li>
				<li><a href="http://www.facebook.com/share.php?u=<?php print urlencode(full_url());?>&amp;t=<?php print urlencode("Speclative - The speculative news laxative");?>" id="facebook" class="replace social-media" title="Share on Facebook">Share on Facebook<span></span></a></li>
				<li><a href="http://digg.com/submit?phase=2&amp;url=<?php print urlencode(full_url());?>&amp;title=<?php print urlencode("Speclative - The speculative news laxative");?>" id="digg" class="replace social-media" title="Share on Digg">Share on Digg<span></span></a></li>
				<li><a href="http://delicious.com/post?url=<?php print urlencode(full_url());?>&amp;title=<?php print urlencode("Speclative - The speculative news laxative");?>" id="delicious" class="replace social-media" title="Bookmark on Delicious">Bookmark on Delicious<span></span></a></li>
				<li><a href="http://twitter.com/home?status=<?php print urlencode("Speclative - The speculative news laxative: ".full_url());?>" id="twitter" class="replace social-media" title="">Share on Twitter<span></span></a></li>
				<li><a href="http://www.tumblr.com/share?v=3&amp;u=<?php print urlencode(full_url());?>&amp;t=<?php print urlencode("Speclative - The speculative news laxative");?>" id="tumblr" class="replace social-media" title="Post to Tumblr">Post to Tumblr<span></span></a></li>
				<li><a href="http://www.stumbleupon.com/submit?url=<?php print urlencode(full_url());?>&amp;title=<?php print urlencode("Speclative - The speculative news laxative");?>" id="stumbleupon" class="replace social-media" title="StumbleUpon">StumbleUpon<span></span></a></li>
				<li><a href="http://reddit.com/submit?url=<?php print urlencode(full_url());?>&amp;title=<?php print urlencode("Speclative - The speculative news laxative");?>" id="reddit" class="replace social-media" title="Share on Reddit">Share on Reddit<span></span></a></li>
				<li><a href="http://posterous.com/share?linto=<?php print urlencode(full_url());?>&amp;title=<?php print urlencode("Speclative - The speculative news laxative");?>" id="posterous" class="replace social-media" title="Post to Posterous">Post to Posterous<span></span></a></li>
			</ul>
			
			<div id="credit">
				<?php
				$c = new Counter();
				if ($c->select(1, 'id')) {
					$count = $c->count;
				} else {
					$count = "Lots of";
				}
				?>
				<p id="credit-stats"><?php print $count;?> pills popped.</p>
			</div>		
		</footer>	
		<!-- #footer -->
		
		<!-- always read the label -->
		<p id="read-label">Always read<br>the label</p>
			
	</div>
	<!-- #container -->	
	
	<!-- link to pository -->
	<a href="http://pository.scott.ee/" title="Pository: The negative news suppository" class="replace" id="other-banner">Pository: The negative news suppository<span></span></a>
	
	<!-- javascript -->
	<script src="/combine.php?type=javascript&amp;files=jquery.js,slider.js,js.js" type="text/javascript"></script>

	<!-- analytics -->
	<script type="text/javascript">
		var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
		document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
		try { var pageTracker = _gat._getTracker("UA-XXXX-XX"); pageTracker._trackPageview(); } catch(err) {}
	</script>
	
</body>
</html>
<?php
	// grab output buffer, compress and print
	$content = ob_get_contents();
	ob_end_clean();
	$search = array('/\>[^\S ]+/s','/[^\S ]+\</s','/(\s)+/s');
	$replace = array('>','<','\\1');
	$content = preg_replace($search, $replace, $content);
	echo $content;

    // page generation time 
    $end = round(microtime(), 4);
	$generation = $end - $start;
	print "<!-- page generated in " . $generation . " seconds -->";
}
?>