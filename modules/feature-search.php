<?php
register_module([
	"name" => "Search",
	"version" => "0.1",
	"author" => "Starbeamrainbowlabs",
	"description" => "Adds proper search functionality to Pepperminty Wiki. Note that this module, at the moment, just contains test code while I figure out how best to write a search engine.",
	"id" => "feature-search",
	"code" => function() {
		add_action("index", function() {
			global $settings, $env;
			
			$breakable_chars = "\r\n\t .,\\/!\"£$%^&*[]()+`_~#";
			
			header("content-type: text/plain");
			
			$source = file_get_contents("$env->page.md");
			
			$index = search::index($source);
			
			var_dump($env->page);
			var_dump($source);
			
			var_dump($index);
		});
	}
]);

class search
{
	// Words that we should exclude from the inverted index.
	public static $stop_words = [
		"a", "about", "above", "above", "across", "after", "afterwards", "again",
		"against", "all", "almost", "alone", "along", "already", "also",
		"although", "always", "am", "among", "amongst", "amoungst", "amount", 
		"an", "and", "another", "any", "anyhow", "anyone", "anything", "anyway",
		"anywhere", "are", "around", "as", "at", "back", "be", "became",
		"because", "become", "becomes", "becoming", "been", "before",
		"beforehand", "behind", "being", "below", "beside", "besides",
		"between", "beyond", "bill", "both", "bottom", "but", "by", "call",
		"can", "cannot", "cant", "co", "con", "could", "couldnt", "cry", "de",
		"describe", "detail", "do", "done", "down", "due", "during", "each",
		"eg", "eight", "either", "eleven", "else", "elsewhere", "empty",
		"enough", "etc", "even", "ever", "every", "everyone", "everything",
		"everywhere", "except", "few", "fifteen", "fify", "fill", "find",
		"fire", "first", "five", "for", "former", "formerly", "forty", "found",
		"four", "from", "front", "full", "further", "get", "give", "go", "had",
		"has", "hasnt", "have", "he", "hence", "her", "here", "hereafter",
		"hereby", "herein", "hereupon", "hers", "herself", "him", "himself",
		"his", "how", "however", "hundred", "ie", "if", "in", "inc", "indeed",
		"interest", "into", "is", "it", "its", "itself", "keep", "last",
		"latter", "latterly", "least", "less", "ltd", "made", "many", "may",
		"me", "meanwhile", "might", "mine", "more", "moreover", "most",
		"mostly", "move", "much", "must", "my", "myself", "name", "namely",
		"neither", "never", "nevertheless", "next", "nine", "no", "none",
		"nor", "not", "nothing", "now", "nowhere", "of", "off", "often", "on",
		"once", "one", "only", "onto", "or", "other", "others", "otherwise",
		"our", "ours", "ourselves", "out", "over", "own", "part", "per",
		"perhaps", "please", "put", "rather", "re", "same", "see", "seem",
		"seemed", "seeming", "seems", "serious", "several", "she", "should",
		"show", "side", "since", "sincere", "six", "sixty", "so", "some",
		"somehow", "someone", "something", "sometime", "sometimes",
		"somewhere", "still", "such", "system", "take", "ten", "than", "that",
		"the", "their", "them", "themselves", "then", "thence", "there",
		"thereafter", "thereby", "therefore", "therein", "thereupon", "these",
		"they", "thickv", "thin", "third", "this", "those", "though", "three",
		"through", "throughout", "thru", "thus", "to", "together", "too", "top",
		"toward", "towards", "twelve", "twenty", "two", "un", "under", "until",
		"up", "upon", "us", "very", "via", "was", "we", "well", "were", "what",
		"whatever", "when", "whence", "whenever", "where", "whereafter",
		"whereas", "whereby", "wherein", "whereupon", "wherever", "whether",
		"which", "while", "whither", "who", "whoever", "whole", "whom", "whose",
		"why", "will", "with", "within", "without", "would", "yet", "you",
		"your", "yours", "yourself", "yourselves"
	];

	public static function index($source)
	{
		$source = html_entity_decode($source, ENT_QUOTES);
		$source_length = strlen($source);
		
		$index = [];
		
		// Regex from 
		$terms = preg_split("/((^\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+$))/", $source, -1, PREG_SPLIT_NO_EMPTY);
		$i = 0;
		foreach($terms as $term)
		{
			$nterm = strtolower($term);
			
			// Skip over stop words (see https://en.wikipedia.org/wiki/Stop_words)
			if(in_array($nterm, self::$stop_words)) continue;
			
			if(!isset($index[$nterm]))
			{
				$index[$nterm] = [ "freq" => 0, "offsets" => [] ];
			}
			
			$index[$nterm]["freq"]++;
			$index[$nterm]["offsets"][] = $i;
			
			$i++;
		}
		
		return $index;
	}
	
	/*
	 * @summary Sorts an index alphabetically. Will also sort an inverted index.
	 * 			This allows us to do a binary search instead of a regular
	 * 			sequential search.
	 */
	public static function sort_index(&$index)
	{
		ksort($index, SORT_NATURAL);
	}
	
	/*
	 * @summary Compares two *regular* indexes to find the differences between them.
	 * 
	 * @param {array} $indexa - The old index.
	 * @param {array} $indexb - The new index.
	 * @param {array} $changed - An array to be filled with the nterms of all
	 * 							 the changed entries.
	 * @param {array} $removed - An array to be filled with the nterms of all
	 * 							 the removed entries.
	 */
	public static function compare_indexes($indexa, $indexb, &$changed, &$removed)
	{
		foreach ($indexa as $nterm => $entrya)
		{
			if(!isset($indexb[$nterm]))
				$removed[] = $nterm;
			$entryb = $indexb[$nterm];
			if($entrya !== $entryb) $changed[] = $nterm;
		}
	}
	
	/*
	 * @summary Reads in and parses an inverted index.
	 */
	// Todo remove this function and make everything streamable
	public static function parse_invindex($invindex_filename) {
		$invindex = json_decode(file_get_contents($invindex_filename), true);
		return $invindex;
	}
	
	/*
	 * @summary Merge an index into an inverted index.
	 */
	public static function merge_into_invindex(&$invindex, $pageid, &$index, &$removals = [])
	{
		// Remove all the subentries that were removed since last time
		foreach($removals as $nterm)
		{
			unset($invindex[$nterm][$pageid]);
		}
		
		// Merge all the new / changed index entries into the inverted index
		foreach($index as $nterm => $newentry)
		{
			// If the nterm isn't in the inverted index, then create a space for it
			if(!isset($invindex[$nterm])) $invindex[$nterm] = [];
			$invindex[$nterm][$pageid] = $newentry;
		}
	}
	
	public static function save_invindex($filename, &$invindex)
	{
		file_put_contents($filename, json_encode($invindex));
	}
}

?>
