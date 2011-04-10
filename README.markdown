# Sherlock (BETA) #

Lets you inspect the url.

## Installation

* Copy the /system/expressionengine/third_party/sherlock/ folder to your /system/expressionengine/third_party/ folder

## Usage

* Use to detect the type of page based on the url  

		{exp:sherlock:page_type}
			{if is_pagination} 
				There are a lot of these.
			{/if} 
			{if is_pagination && is_category}
				There are a lot of these to filter.
			{/if} 
			{if page_type == 'category'} 
				Lets filter these entries a little.
			{/if}
		{/exp:sherlock:page_type}

* Get a category_id based upon a url segment  

		{exp:sherlock:category_id category_url_title="{segment_4}"}
			{exp:weblog:entries weblog="news" category="{category_id}"}
				<ul>
					<li><a href="{url_title_path="news"}">{title}</a></li>
				</ul>
			{/exp:weblog:entries}
		{/exp:sherlock:category_id}

* Get query string variables out of the url  

		{exp:sherlock:get}
			<p>{get:name} - {get:gender}</p>
		{/exp:sherlock:get}


* You also have access to all the parts of the url  

		{exp:sherlock:parts}
			<p>{protocal}://{host}:{port}{uri}</p>
			{if uri_part_1 == 'home'}
				<p>Home at last!</p>
			{/if}
		{/exp:sherlock:parts}

* And you can decode url encode strings  

		{exp:sherlock:url_decode string="Cheese%20is%20awesome!"}

		
## Issues

Please log any issues or problems here: <https://github.com/themusicman/Sherlock/issues>
