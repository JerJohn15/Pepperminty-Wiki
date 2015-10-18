<?php
register_module([
	"name" => "Redirect pages",
	"version" => "0.1",
	"author" => "Starbeamrainbowlabs",
	"description" => "Adds support for redirect pages. Uses the same syntax that Mediawiki does.",
	"id" => "feature-redirect",
	"code" => function() {
		register_save_preprocessor(function(&$index_entry, &$pagedata) {
			$matches = [];
			if(preg_match("/^# ?REDIRECT ?\[\[([^\]]+)\]\]/i", $pagedata, $matches) === 1)
			{
				error_log("matches: " . var_export($matches, true));
				// We have found a redirect page!
				// Update the metadata to reflect this.
				$index_entry->redirect = true;
				$index_entry->redirect_target = $matches[1];
			}
			else
			{
				// This page isn't a redirect. Unset the metadata just in case.
				if(isset($index_entry->redirect))
					unset($index_entry->redirect);
				if(isset($index_entry->redirect_target))
					unset($index_entry->redirect_target);
			}
		});
	}
]);

?>