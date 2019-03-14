# Studio 24 - headless preview
## What does this plugin do

This plugin will make it possible to preview posts/custom posts you 
are working on, on your decoupled frontend website, by exposing revisions. Which you can then fetch on your frontend with the provided token.

## Activation

When you activate the plugin, a table will be generated:
	 
	 <wpprefix>_studio24_preview_tokens

The table consists of 3 columns:
- token_id
	- A generated token using php random_bytes function
- parent_post_id
	- The parent post ID
- creation_time
	- This will contain the time the token was created
	    - We need this later to clean up unused tokens.
	    - A cronjob will be activated to remove any tokens in the table every hour.

## Deactivation

On deactivation this table with all it's contents will be deleted.
The cronjob will also be removed.

**Remember**: You will no longer be able to get previews on your frontend

## Settings

You need to set the url on which *frontend* application you want to preview your posts.

**TODO** You can set the time for deletion of the tokens in hours. Standard is 1 hour.

## How it works

##### Overview of posts
*<small>/wp-admin/edit.php</small>*

This plugin adds an extra link to the post quick actions. The link is labeled *'Headless preview'*.

##### Classic editor
*<small>/wp-admin/post.php?post=?&action=edit | /wp-admin/post-new.php</small>*

This plugin adds an extra meta box on the right side. It's labeled *'Headless preview'*. 
Here you can find the button to open the new page and a settings overview.

##### Gutenberg editor
*<small>/wp-admin/post.php?post=?&action=edit | /wp-admin/post-new.php</small>*

This plugin adds a sidebar to the editor. On the right side there will be a button to toggle this sidebar. 
It's labeled *'Headless preview'*. Here you can find the button to open the new page and a settings overview.

In all cases:
If you click the link/button, a new page *<small>(the provided url)</small>* will open.

## Requirements
A front-end page that handles the token and requests the data. 
It is the **responsibility of the user** to provide the necessary functionality on the front-end site.

## Fetching the preview

### Route

A new route will be available in the REST API.
You can access the data through the following url.
    
    wp-json/preview-studio-24/v1/{token_id}

Once you click on 'Headless Preview' you will automatically be redirected to the frontend with get parameters.

https://providedfrontend.co.uk/previewurl?token=abc123...xyz789?post_type=post

- token
    - You'll need this to authenticate in order to get the post data
- post_type
    - This can be used on the front-end to correctly display the data

*Note: Once you call this api with a token, it will be deleted.*
<br/>
*Note: We advice to use a dedicated route in your frontend that expects preview data.*

The following responses can be returned when you call the api:

```json
{
    "code": "token_not_found",
    "message": "Invalid token id",
    "data": {
        "status": 404
    }
}
```
- You already used the token
- The token is corrupted or non existing
 
```json
{
    "code": "rest_no_route",
    "message": "No route was found matching the URL and request method",
    "data": {
        "status": 404
    }
}
```

- Your url is malformed
```json
{
    "code": "post_not_found",
    "message": "Invalid post id",
    "data": {
        "status": 404
    }
}
```
- The post doesn't exist or has been deleted

```json
{
    "ID": 66,
    "post_author": "1",
    "post_date": "2019-03-14 13:39:06",
    "post_date_gmt": "2019-03-14 13:39:06",
    "post_content": "<!-- wp:paragraph -->\n<p>This is a test post</p>\n<!-- /wp:paragraph -->",
    "post_title": "Hello world!",
    "post_excerpt": "",
    "post_status": "inherit",
    "comment_status": "closed",
    "ping_status": "closed",
    "post_password": "",
    "post_name": "62-revision-v1",
    "to_ping": "",
    "pinged": "",
    "post_modified": "2019-03-14 13:39:06",
    "post_modified_gmt": "2019-03-14 13:39:06",
    "post_content_filtered": "",
    "post_parent": 62,
    "guid": "http://localhost/wordpress/2019/03/14/62-revision-v1/",
    "menu_order": 0,
    "post_type": "revision",
    "post_mime_type": "",
    "comment_count": "0",
    "filter": "raw"
}
```
- You successfully fetched the latest revision

*Note: If the post has multiple revisions. note that only the last revision will be returned.*

## Known issues
- Unable to re-enable sidebar when unpinned (Gutenberg)
- Token doesn't regenerate when clicking the link