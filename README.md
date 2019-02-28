## What does this plugin do

This plugin will make it possible to preview the posts/custom posts you 
are working on, on your decoupled frontend website.

## Activation

When you activate the plugin, a table will be generated:
	 
	 wpprefix_studio24_preview_tokens

The table consists of 3 columns:
- token_id
	- A generated token using php random_bytes function
- parent_post_id
	- The parent post ID
- creation_time
	- This will contain the time the 'preview' was created.

A cronjob will also be activated to remove any tokens in the table every hour. This means that once you click preview, but for any reason don't call the api the token will be available for 1 hour.

## Deactivation

On deactivation this table with all it's contents will be deleted.

**Remember**: You will no longer be able to get previews on your frontend

## Settings

You need to set the url on which *frontend* application you want to preview your posts.

You can set the time for deletion of the tokens in hours. Standard is 1 hour.

## Fetching the preview

Once you click on 'Preview' you will automatically be redirected to the frontend with a get parameter.

	myfronted.org.uk/dedicatedroute?token=5bdwq...

**Note**: We advice to use a dedicated route in your frontend that expects preview data.

Use this token to call te backend api to get a post. The following events can happen when you call:
	
	If you have send the wrong token to the api you will get a 404 Token_Not_found response.

	If the post has not been found, a 404 Post_Not_Found will be returned.

	If the post has no revisions yet, the post itself will be returned.

	If the post has multiple revisions. note that only the last revision will be returned.

## Routing

A new route will be available in the REST API. This will be the route to which your frontend should be calling to get the preview data.
	
	wp-json/preview-studio-24/v1/{token_id}

*Note: Once you call this api, the token will be deleted.*
