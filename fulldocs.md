# PMP WordPress Plugin

[![Build Status](https://travis-ci.org/publicmediaplatform/pmp-wordpress.svg?branch=master)](https://travis-ci.org/publicmediaplatform/pmp-wordpress)
[![Latest Release](https://img.shields.io/github/release/publicmediaplatform/pmp-wordpress.svg)](https://github.com/publicmediaplatform/pmp-wordpress/releases/latest)

Use this plugin to integrate the [Public Media Platform](http://publicmediaplatform.org/) with WordPress. Integration includes pulling stories and other content from the PMP into WordPress drafts and posts, and pushing WordPress posts into the PMP. Define simple or advanced searches for PMP content and save custom searches for reuse, with or without autopublishing on your WordPress site. Create PMP Groups, Properties, and Series from the plugin and push them to the PMP. Use of this plugin requires that you [register for a PMP account to get API keys](https://support.pmp.io/register).

Also see this project in the [official Wordpress plugin directory](https://wordpress.org/plugins/public-media-platform/).

Built by [INN Labs](https://labs.inn.org/).

## Table of contents

- [Installation](#installation)
- [Settings](#settings)
- [Pull Content from the PMP](#pull-content-from-the-pmp)
    - [Keyword search](#keyword-search)
- [Import Content](#import-content)
- [(Un)subscribe from content update](#unsubscribe-from-content-updates)
- [Pushing content to PMP](#pushing-content-to-pmp)
- [Groups & permissions](#groups--permissions)
    - [Create and managing groups](#creating-and-managing-groups)
    - [Modify an existing group](#modify-an-existing-group)
    - [Setting the default group for new content](#setting-the-default-group-for-new-content)
    - [Managing users](#managing-users)
        - [Adding users](#adding-users)
        - [Removing users](#removing-users)
        - [Saving changes](#saving-changes)
- [Series](#series)
    - [Setting the default Series](#setting-the-default-series)
- [Properties](#properties)
    - [Setting the default Property](#setting-the-default-property)
- [Saved search queries](#saved-search-queries)
    - [Automatically importing posts based on a saved search query](#automatically-importing-posts-based-on-a-saved-search-query)
    - [Saving a search query](#saving-a-search-query)
        - [Auto-assign categories to imported posts](#auto-assign-categories-to-imported-posts)
    - [Viewing and editing a search query](#viewing-and-editing-a-search-query)
    - [View all saved search queries](#view-all-saved-search-queries)

## Installation

Follow the standard procedure for [automatic plugin installation](https://codex.wordpress.org/Managing_Plugins#Automatic_Plugin_Installation), and search for "PMP" or "Public Media Platform".  Using the [official plugin](https://wordpress.org/plugins/search.php?type=term&q=PMP) from the Wordpress plugin directory allows you to automatically get updates.

If you'd prefer the bleeding edge `master` version of the plugin, you'll have to install it manually, following the standard procedure for [manual plugin installation](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).  You can get the [latest code zip here](https://github.com/publicmediaplatform/phpsdk/archive/master.zip).

Once the plugin files are installed, activate the plugin via the WordPress dashboard.

## Settings

To use the PMP WordPress plugin, you'll need to specify a **Client ID** and **Client Secret** via the **Public Media Platform** > **Settings** page in the WordPress dashboard. In the PMP Environment dropdown select Production unless you are setting up a sandbox environment for testing. If you want to automatically pull updates if a story is revised in the PMP, check the Enable box.

![Settings](/assets/img/largo-PMP-settings-blank.png)

If you don't yet have a Client ID and Client Secret, you'll probably need to [request an account with the PMP](https://support.pmp.io/register).

## Pull Content from the PMP

### Keyword Search

Search for content by visiting the **Public Media Platform** > **Search** link in the WordPress dashboard. Perform a simple search by entering keywords in the search field. If you want to search by a specific phrase, use quotes around the phrase, like "Chicago fire".

You can also limit the search to story titles etc by using these prefixes:

- title:”search term”
- teaser:”search term”
- tags:”search term”
- content:”search term”
- byline:”name”

Here's an example of search results for Chicago.  Since we didn't use a prefix, this search will return stories that have "Chicago" anywhere in the story:

![Search results](/assets/img/pmp-search-in-largo-chicago.png)

## Advanced Search

You can further refine a search for PMP content by clicking "Show advanced options" and filtering by several options. Select a content creator, search by media type, tag, PMP GUID, or PMP collection GUID (the GUIDs can be found using [the PMP search portal](https://support.pmp.io/)). 

You can filter by content creator and add a tag to refine your search even further:

![Advanced Search](/assets/img/pmp-advanced-search-in-largo.png)

The search results give you a pretty good preview of available stories. Each story has a link to its preview page on the PMP:

![Story link to preview page on the PMP](/assets/img/pmp-story-in-largo-search-results-link.png)

You can click this link to see the story summary on the PMP:

![Story link to preview page on the PMP](/assets/img/search-result-on-pmp.png)

If you want to see the full story on the public media site where it was original published, click the headline on the PMP story summary:

![Story on the Hear and Now website](/assets/img/here-and-new-story.png)

Note that you can [save a search query for reuse later](#saved-search-queries). So once you set up a search that returns stories for a particular content source or topic, you can run the saved search to import more matching stories in the future. 

## Import Content

Now to the important part: Pulling PMP content into your WordPress site from the search results. From the search results list, you can choose to create a draft post or choose to immediately publish a post based on a search result.

![Search result](/assets/img/pmp-story-in-largo-search-results.png)

After clicking one of the "Create draft" or "Publish" links, you'll see a confirmation dialog:

![Search result dialogue box](/assets/img/save-draft-dialogue-pmp-largo.png)

Upon confirming, a new post will be created and you will be redirected to the post's edit page:

![Editing draft of PMP story in WordPress](/assets/img/draft-pmp-post-in-largo.png)

You can now edit the PMP story in the usual WordPress way. Note that if you subscribe to updates for this post (which is enabled by default), any future changes to the PMP story will automatically overwrite your existing post.

## (Un)Subscribe from Content Updates

By default, the plugin periodically checks for updates to all content that you import from PMP. Any updates to stories in the PMP will automatically update the corresponding posts on your site. Typically this is useful, but if you've edited the post for your site you might want to disable PMP updates. 

You can disable automatic updates to each post via the **PMP: Document information** meta box on the post edit page:

![PMP plugin settings for subscribing to post updates](/assets/img/post-pmp-subscribe-settings.png)

To unsubscribe to updates, simply uncheck "Subscribe to updates for this post" and then click "Publish", "Update" or "Save Draft" to save.

## Pushing Content to PMP

To push a new story to the PMP, create and edit your new post as you would any other. When you are ready to publish your post, click the "Publish" button as you normally would. Note that you can't push the post to the PMP until it's published on your site.

![Before publishing Push to PMP is disabled](/assets/img/post-prepush-to-pmp.png)

If you would like to push the post to the PMP, click the "Push to PMP" button in the **PMP: Document information** meta box after your post is published. Note that as you push the post to the PMP you have options to assign it to a Group, Series, or Property. We [cover these things below](#creating-and-managing-groups).

![After publishing the post Push to PMP is enabled](/assets/img/post-published-prepush-to-pmp.png)

Once your post is published, if you make changes to it in WordPress and would like to push your changes to the PMP, click the "Push to PMP" button located in the **PMP: Document information** meta box. Note that the PMP guid for the post is displayed in the meta box (actually only part of the guid is displayed), with a link to the story on the PMP portal.

![Update the post and push the changes to the PMP](/assets/img/post-published-and-pushed-to-pmp.png)

After pushing a post to the PMP it will appear on that platform within seconds where other PMP users can discover and reuse it, depending on the permissions you set for it.

![A post pushed to the PMP](/assets/img/pmp-story-bradbury.png)

Note that any WordPress Tags you add to the post will be pushed to the PMP. Likewise if you assign a Group, Series, or Property to the post they will become part of the data associated with the post when pushed to the PMP.

## Groups & Permissions

By default all content in the PMP is public, unless access is restricted by the publisher. Access can be limited by creating a Group, and then adding Users to the Group. See the [PMP Docs on permissions](https://support.pmp.io/guides#pmp-terminology-permissions) for more on how this works in the PMP.

The PMP WordPress plugin allows you to create a Group which will get pushed to the PMP.  After you create a new Group you can add users to it. But note that you can only add existing PMP users, that is, people or organizations who have registered for a PMP account. You can add and delete users in a group and change the name of the group, and changes will propagate to the PMP. But you can’t delete a group from the PMP plugin once you create it. 

Tip: You can see [all existing PMP users in the PMP Search portal](https://support.pmp.io/search?advanced=1&searchsort=date&profile=user).

### Creating and Managing Groups

To manage your own PMP Groups & Permissions, navigate to **Public Media Platform** > **Groups & Permissions** in the WordPress dashboard.

To create a new group, click the "Create new group" button at the top of the Groups & Permissions page. You'll be met with a "Create a group" prompt where you can specify your new group's title and tags.

![creating a new PMP group](/assets/img/create-a-group-pmp-plugin.png)

The title field is required.

The tags field should be a comma separated list. For example:

    my_first_tag, another tag, yet-another-tag

The tags will add to the data about the group in the PMP.

### Modify an Existing Group

To modify the title or tags for an existing group, click the "Modify" link below the name of the group you wish to modify. You can change the name of the group, and add users and tags, but once created you can't delete a group.

### Setting the Default Group for New Content

To set the default group to which all new content pushed to PMP will be added, click the "Set as default" link below the name of the group of your choice.

You will asked to confirm your choice:

![Confirm default group](/assets/img/set-default-group-for-pmp-push.png)

After clicking "Yes" to confirm, the confirmation prompt will close and the list of groups will update. The group you set as the default will appear with "(default)" near its name:

![Default group set](/assets/img/pmp-default-group.png)

### Managing Users

To manage the users for a group, click the "Manage users" link below the group of your choice.

You'll see a user management prompt appear:

![Manage users](/assets/img/pmp-user-dialogue.png)

#### Adding Users

To add a new user, click on the text field towards the bottom of the prompt and start typing a user's name:

![Search users](/assets/img/pmp-add-user.png)

As you type a user's name, suggestions will appear below the text field. Add a user by clicking one of the suggestions that appears. The user's name will be added to the list above the search field:

![User added](/assets/img/pmp-user-added.png)

Tip: If you have any problem finding registered PMP users, you can see the [complete list of users on the PMP search portal](https://support.pmp.io/search?advanced=1&searchsort=date&profile=user).

#### Removing Users

To remove a user, click the "x" to the right of their name.

#### Saving Changes

Once you've added or removed users from a group, you must click the "Save" button for your changes to take effect.

## Series

Series are somewhat loosely defined in the PMP as a collection of related stories. A series may correspond to a broadcast program, or reports on a particular topic or beat, or any other relationship between stories you care to make. (See the [Terminology section of the PMP User Docs](https://support.pmp.io/guides#pmp-terminology) for more on what a Series is in the PMP.)

You can create a new Series in the PMP plugin, and add relevant Tags to it which become part of the series data in the PMP. The plugin will push new series and their tags to the PMP. You can edit the series name and its tags, and changes will propagate to the PMP. But like Groups you can’t delete a series in the plugin, you can only modify it.

To manage series, navigate to **Public Media Platform** > **Series** in the WordPress dashboard.

### Create a New Series

Creating a series is much like creating a group. Click the New Series button:

![creating a new series in the PMP plugin](/assets/img/create-a-series.png)

### Setting the Default Series

To set the default series to which all new content pushed to PMP will be added, click the "Set as default" link below the series of your choice.

You will asked to confirm your choice:

![Confirm default series](/assets/img/set-default-series.png)

After clicking "Yes" to confirm, the confirmation prompt will close and the list of series will update. The series you set as the default will appear with "(default)" near its name:

![Default series set](/assets/img/default-series.png)

## Properties

Properties are defined in the PMP as the highest level of organization for a collection of content. It could be akin to a brand for a broadcast program, or an entire news organization. (See the [Terminology section of the PMP User Docs](https://support.pmp.io/guides#pmp-terminology) for more on what a Property is in the PMP.)

Like Series and Groups, you can create new Properties in the PMP plugin and add tags to them, and they will propagate to the PMP. You can modify the title and tags of a property in the plugin, and this will update the PMP. You can’t delete properties in the PMP plugin once they are created, you can only modify them to suit your needs.

To manage properties, navigate to **Public Media Platform** > **Properties** in the WordPress dashboard.

Create a new property by clicking the **Create new properties** button, then enter a Title for the property and any relevant tags:

![creating new PMP property](/assets/img/create-pmp-property.png)

### Setting the Default Property

To set the default property to which all new content pushed to PMP will be added, click the "Set as default" link below the series of your choice.

You will asked to confirm your choice:

![Confirm default property](/assets/img/set-default-property.png)

After clicking "Yes" to confirm, the confirmation prompt will close and the list of series will update. The property you set as the default will appear with "(default)" near its name:

![Default property set](/assets/img/default-property-set.png)

## Saved Search Queries

### Automatically Importing Posts Based on a Saved Search Query

You can save search queries to make it easier to view results and import new content in the future.

Optionally, when saving or editing a search query, you can specify that new search results be automatically imported. For search results that are automatically imported, the PMP plugin can either:

- Create draft posts from results for the saved query
- Publish posts from results for the saved query

Note: the default behavior for saved search queries is to do nothing with search results.

### Saving a Search Query

Start by visiting the **Public Media Platform** > **Search** link in the WordPress dashboard as you would to [search for content](#pull-content-from-the-pmp).

Configure the search form to your liking. For example:

![Saved search query start](/assets/img/search-chicago-pri.png)

Once your have your query configured and are ready to save, click the "Save query" button. A modal window will appear, asking you to specify a title for the saved search query. 

You can specify what action to take with new results for the saved search query whenever it's run: create draft posts for review, publish posts immediately, or do nothing with the results which allows you to select posts from the results for creating drafts or posts.

At this point, you might also like to [auto-assign categories to imported posts](#auto-assign-categories-to-imported-posts).

![Saved search query modal](/assets/img/saving-a-pmp-search.png)

Once you have specified a title and an action, click the "Save" button.


#### Auto-assign Categories to Imported Posts

When saving a search query, you also have the opportunity to set categories to be applied to all posts automatically imported for said query.

For example, if you want to construct a search query for "Chicago marketplace" and funnel all posts found for that query to your site's "News" category, you would:

1. Ensure you have a "News" category on your site by navigating to Posts > Categories in the WordPress dashboard.
2. Navigate to Public Media Platform > Search and perform the steps to save a search query [outlined above](#saving-a-search-query).
3. Before clicking the "Save" button to save your query, select the "News" category from the category list in the "Save the current query" dialog.

See:

![Saved search query select categories](/assets/img/saving-news-search.png)

### Viewing and Editing a Search Query

After clicking the "Save" button to save a search query, the page will automatically reload and present you with your newly saved query:

![Saved search query edit](/assets/img/saved-pmp-search.png)

Note the notice above the search form that reads:

    "Viewing saved query: "Chicago (PRI)"

Also note in the location where there was previously a "Save query" button, you are now presented with an "Edit query" button.

Clicking the "Edit query" button allows you to change your preferences for the saved query.

For instance, if you wanted to change the action taken for new results for the saved query to automatically publish, you would:

- Click "Edit query"
- In the modal window that appears, select "Publish posts from results for this query"
- Click the "Save" button

See:

![Saved search query edit modal](/assets/img/edit-news-search.png)

### View All Saved Search Queries

To see all saved search queries, visit Public Media Platform > Manage saved searches link in the WordPress dashboard. From there, you see the complete list of saved search queries:

![Manage saved searches list](/assets/img/view-saved-searches.png)

Actions you can take for each saved search query include:

- "View and edit this search query"
- "Delete"

Clicking "View and edit this search query" will land you on the page described in the [Viewing and editing a search query](#viewing-and-editing-a-search-query) section of the documentation.

Clicking "Delete" will present you will a confirmation modal:

![Delete a saved search query](/assets/img/delete-search-dialogue.png)

## All Set

Now you're ready pull great content from the PMP for use on your WordPress site, and push you own fine work to the PMP for others in the public media ecosystem to share with their digital audiences. Do great things!
