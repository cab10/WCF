{capture assign='headContent'}
	{if $pageNo < $pages}
		<link rel="next" href="{link controller='ArticleList'}pageNo={@$pageNo+1}{/link}">
	{/if}
	{if $pageNo > 1}
		<link rel="prev" href="{link controller='ArticleList'}{if $pageNo > 2}pageNo={@$pageNo-1}{/if}{/link}">
	{/if}
	
	{if $__wcf->getUser()->userID}
		<link rel="alternate" type="application/rss+xml" title="{lang}wcf.global.button.rss{/lang}" href="{link controller='ArticleFeed'}at={@$__wcf->getUser()->userID}-{@$__wcf->getUser()->accessToken}{/link}">
	{else}
		<link rel="alternate" type="application/rss+xml" title="{lang}wcf.global.button.rss{/lang}" href="{link controller='ArticleFeed'}{/link}">
	{/if}
{/capture}

{capture assign='headerNavigation'}
	<li><a rel="alternate" href="{if $__wcf->getUser()->userID}{link controller='ArticleFeed'}at={@$__wcf->getUser()->userID}-{@$__wcf->getUser()->accessToken}{/link}{else}{link controller='ArticleFeed'}{/link}{/if}" title="{lang}wcf.global.button.rss{/lang}" class="jsTooltip"><span class="icon icon16 fa-rss"></span> <span class="invisible">{lang}wcf.global.button.rss{/lang}</span></a></li>
	{if ARTICLE_ENABLE_VISIT_TRACKING}
		<li class="jsOnly"><a href="#" title="{lang}wcf.article.markAllAsRead{/lang}" class="markAllAsReadButton jsTooltip"><span class="icon icon16 fa-check"></span> <span class="invisible">{lang}wcf.article.markAllAsRead{/lang}</span></a></li>
	{/if}
{/capture}

{if $__wcf->getSession()->getPermission('admin.content.article.canManageArticle') || $__wcf->getSession()->getPermission('admin.content.article.canContributeArticle')}
	{capture assign='contentHeaderNavigation'}
		<li><a href="{link controller='ArticleAdd' isACP=true}{/link}" class="button"><span class="icon icon16 fa-pencil"></span> <span>{lang}wcf.acp.article.add{/lang}</span></a></li>
	{/capture}
{/if}

{include file='header'}

{hascontent}
	<div class="paginationTop">
		{content}
			{pages print=true assign='pagesLinks' controller='ArticleList' link="pageNo=%d"}
		{/content}
	</div>
{/hascontent}

{if $objects|count}
	<div class="section">
		{include file='articleListItems'}
	</div>
{else}
	<p class="info">{lang}wcf.global.noItems{/lang}</p>
{/if}

<footer class="contentFooter">
	{hascontent}
		<div class="paginationBottom">
			{content}{@$pagesLinks}{/content}
		</div>
	{/hascontent}
	
	{hascontent}
		<nav class="contentFooterNavigation">
			<ul>
				{content}{event name='contentFooterNavigation'}{/content}
			</ul>
		</nav>
	{/hascontent}
</footer>

{if ARTICLE_ENABLE_VISIT_TRACKING}
	<script data-relocate="true">
		require(['WoltLabSuite/Core/Ui/Article/MarkAllAsRead'], function(UiArticleMarkAllAsRead) {
			UiArticleMarkAllAsRead.init();
		});
	</script>
{/if}

{include file='footer'}
