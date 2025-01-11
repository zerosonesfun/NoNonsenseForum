<!DOCTYPE html>
<html lang="en" class="search"><head>
	<meta charset="utf-8">
	<!-- NoNonsense Forum v26 © Copyright (CC-BY) Kroc Camen 2010-2015
	     licensed under Creative Commons Attribution 3.0 <creativecommons.org/licenses/by/3.0/deed.en_GB>
	     you may do whatever you want to this code as long as you give credit to Kroc Camen, <camendesign.com> -->
	<title>Search</title>
	<!-- get rid of IE site compatibility button -->
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<link rel="stylesheet" href="/themes/greyscale/theme.css?v=1.1">
	<link rel="stylesheet" href="/themes/greyscale/custom.css">
	<link rel="canonical" href="/search.php">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- standard fav-icons / mobile tiles -->
	<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
	<link rel="apple-touch-icon" href="/apple-touch-icon.png">
	<!-- Microsoft’s insane IE9+ pinned site syntax: <msdn.microsoft.com/library/gg131029> -->
	<meta name="application-name" content="NoNonsense Forum">
	<meta name="msapplication-starturl" content="http://forum.camendesign.com/">
	<meta name="msapplication-window" content="width=1024;height=600">
	<meta name="msapplication-navbutton-color" content="#222">
	<!-- Windows 8 Tile icon / colour -->
	<meta name="msapplication-TileImage" content="/metro-tile.png">
	<meta name="msapplication-TileColor" content="#222">
</head><body>
<!-- =================================================================================================================== -->
<header id="mast">
	<h1><a href="/">
		<img id="nnf_logo" src="/themes/greyscale/img/logo.png" width="32" height="32" alt=""><!--
		--><span class="nnf_forum-name">NoNonsense Forum</span>
	</a></h1>
	
<form id="search" action="search.php" method="get">
    <div class="search-container">
        <input type="text" id="search-query" name="query" placeholder="Search..." aria-label="Search query">
    </div>
</form>

<script>
// Function to handle the search
document.getElementById('search').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent the default form submission behavior

    const query = document.getElementById('search-query').value.trim();

    if (!query) {
        return; // Exit if the search query is empty
    }

    // Redirect to search.php with the query parameter
    window.location.href = `search.php?query=${encodeURIComponent(query)}`;
});
</script>
	
	<nav>
		<form id="nnf_lang" method="post">
			<img src="/themes/greyscale/img/lang.png" width="20" height="20" alt="Language:" title="Language:">
			<select name="lang" id="lang">
				<option class="nnf_lang" value="" selected>English</option>
			</select>
			<input type="image" src="/themes/greyscale/img/go.png" width="20" height="20" alt="Set language">
		</form>
		<ol id="index">
			<li><a href="/">Index</a></li>
			<li class="nnf_breadcrumb">» <a class="nnf_subforum-name" href="/sub-forum">Sub-forum</a></li>
		</ol>
	</nav>
</header>
<!-- =================================================================================================================== -->
<section id="privacy">

<h1>Search</h1>
<article>
<p></p>

<!-- Search Results -->
<div class="search-results" id="search-results"></div>

<!-- JS/Ajax for Search Results -->
<script>
// Function to get the query parameter from the URL
function getQueryParam(param) {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(param);
}

// Function to handle the search
function handleSearch(page = 1) {
  const query = getQueryParam('query'); // Get the query from the URL

  if (!query) {
    return; // Exit if no query is found in the URL
  }

  // Show loading message while searching
  const resultsContainer = document.getElementById('search-results');
  resultsContainer.innerHTML = 'Searching...';

  // Prepare the AJAX request
  const xhr = new XMLHttpRequest();
  const cacheBuster = `&_=${Date.now()}`; // Unique timestamp to bypass cache
  xhr.open('GET', `/forum/search_rss.php?query=${encodeURIComponent(query)}&page=${page}&limit=5${cacheBuster}`, true);

  // Set up a callback for when the request is complete
  xhr.onload = function() {
    if (xhr.status === 200) {
      try {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(xhr.responseText, 'application/xml');

        const items = xmlDoc.getElementsByTagName('item');
        const totalResults = parseInt(xmlDoc.getElementsByTagName('totalResults')[0]?.textContent || 0);
        const totalPages = parseInt(xmlDoc.getElementsByTagName('totalPages')[0]?.textContent || 1);
        const currentPage = parseInt(xmlDoc.getElementsByTagName('currentPage')[0]?.textContent || 1);

        if (items.length === 0) {
          resultsContainer.innerHTML = 'No results found.';
        } else {
          let resultsHtml = '';
          for (let i = 0; i < items.length; i++) {
            const title = items[i].getElementsByTagName('title')[0]?.textContent || 'No title';
            const link = items[i].getElementsByTagName('link')[0]?.textContent || '#';
            let description = items[i].getElementsByTagName('description')[0]?.textContent || '';

            // Trim description to 100 characters
            if (description.length > 100) {
              description = description.substring(0, 100) + '...';
            }

            // Avoid adding empty <p> tags
            if (description.trim() === '') {
              description = 'No description available.';
            }

            resultsHtml += `
              <div class="search-result-item">
                <h3><a href="${link}" target="_self">${title}</a></h3>
                <p>${description}</p><br>
              </div>
            `;
          }
          resultsContainer.innerHTML = resultsHtml;

          // Remove empty <p></p> tags
          const paragraphs = resultsContainer.querySelectorAll('p');
          paragraphs.forEach(p => {
            if (!p.textContent.trim()) {
              p.remove(); // Remove empty <p> tags
            }
          });
        }

        // Add pagination controls
        let paginationHtml = '';
        if (currentPage > 1) {
          paginationHtml += `<a href="#" onclick="handleSearch(${currentPage - 1}); return false;">Previous</a> `;
        }
        if (currentPage < totalPages) {
          paginationHtml += `<a href="#" onclick="handleSearch(${currentPage + 1}); return false;">Next</a>`;
        }
        resultsContainer.innerHTML += `<div class="pagination">${paginationHtml}</div>`;
        
      } catch (e) {
        resultsContainer.innerHTML = 'Error parsing response.';
        console.error(e);
      }
    } else {
      resultsContainer.innerHTML = 'An error occurred while searching.';
    }
  };

  // Handle network errors
  xhr.onerror = function() {
    resultsContainer.innerHTML = 'Network error. Please try again later.';
  };

  // Send the request
  xhr.send();
}

// Run the search when the page loads if there's a query in the URL
window.addEventListener('DOMContentLoaded', function() {
  handleSearch(1); // Start from page 1
});
</script>
</article>
</section>
<!-- =================================================================================================================== -->
<div id="mods"><p id="nnf_mods-local">
	Moderators for this sub-forum:
	<span id="nnf_mods-local-list"><b class="nnf_mod">Alice</b></span>
</p><p id="nnf_mods">
	Your friendly neighbourhood moderators:
	<span id="nnf_mods-list"><b class="nnf_mod">Bob</b></span>
</p><p id="nnf_members">
	Members of this forum:
	<span id="nnf_members-list"><b>Charlie</b></span>
</p></div>

<footer><p>
	Powered by <a href="http://camendesign.com/nononsense_forum">NoNonsense Forum</a><br>
	<a href="/privacy.php">privacy policy</a>
</p><p id="signin" class="nnf_signed-in">
	Signed in as<br><b class="nnf_signed-in-name">Kroc</b>
</p><form id="signin" class="nnf_signed-out" method="post">
	<input type="submit" name="signin" value="Sign In">
</form></footer>

<script defer><!--
//in iOS tapping a label doesn't click the related input element, we'll add this back in using JavaScript
if (document.getElementsByTagName !== undefined) {
	var labels = document.getElementsByTagName ("label");
	//for reasons completely unknown, one only has to reset the onclick event, not actually make it do anything!!
	for (i=0; i<labels.length; i++) if (labels[i].getAttribute ("for")) labels[i].onclick = function (){}
}
//when the language selector is changed, submit immediately
document.addEventListener("DOMContentLoaded", function () {
  var langSelector = document.getElementById("lang");
  if (langSelector) {
    langSelector.onchange = function () {
      document.getElementById("nnf_lang").submit();
    };
  }
});
--></script>

</body></html>