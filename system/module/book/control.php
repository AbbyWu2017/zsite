<?php if(!defined("RUN_MODE")) die();?>
<?php
/**
 * The control file of book module of chanzhiEPS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPLV1.2 (http://zpl.pub/page/zplv12.html)
 * @author      Tingting Dai <daitingting@xirangit.com>
 * @package     book
 * @version     $Id$
 * @link        http://www.chanzhi.org
 */
class book extends control
{
    /**
     * The default catalog counts when create. 
     */
    const NEW_CATALOG_COUNT = 5;

    /**
     * Index page, locate to browse default.
     * 
     * @access public
     * @return void
     */
    public function index()
    {
        if(isset($this->config->book->index) and $this->config->book->index == 'list')
        {
            $this->view->title = $this->lang->book->list;
            $this->view->books = $this->book->getBookList();
            $this->display();
        }
        else
        {
            if(isset($this->config->book->index) and $this->config->book->index != 'list')
            {
                $book = $this->book->getBookByID($this->config->book->index);
            }
            else
            {
                $book = $this->book->getFirstBook();
            }
            $this->locate(inlink('browse', "nodeID=$book->id", "book=$book->alias") . ($this->get->fullScreen ? "?fullScreen={$this->get->fullScreen}" : ''));
        }
    }

    /**
     * Browse a node of a book.
     * 
     * @param  int    $nodeID 
     * @access public
     * @return void
     */
    public function browse($nodeID)
    {
        $node = $this->book->getNodeByID($nodeID);
        if($node->type == 'book') $this->locate(inlink('read', "articleID={$node->id}")); 
        if($node)
        {
            $nodeID = $node->id;
            $book = $this->book->getBookByNode($node);
            if(($this->config->book->chapter == 'left' or $this->config->book->fullScreen or $this->get->fullScreen) and $this->app->clientDevice == 'desktop') 
            {
                $families = $this->dao->select('id,parent,type,`order`')->from(TABLE_BOOK)
                    ->where('path')->like(",{$nodeID},%")
                    ->orderBy('`order`')
                    ->fetchGroup('parent', 'id');
                
                $allNodes = $this->dao->select('*')->from(TABLE_BOOK)
                    ->where('path')->like("%,{$nodeID},%")
                    ->fetchAll('id');
                $articles = $this->book->getArticleIdList($nodeID, $families, $allNodes);
                
                if($articles)
                {
                    $articles  = explode(',', $articles);
                    $articleID = current($articles);
                    $article   = zget($allNodes, $articleID);
                    $this->locate(inlink('read', "articleID=$articleID", "book=$book->alias&node=$article->alias") . ($this->get->fullScreen ? "?fullScreen={$this->get->fullScreen}" : ''));
                }
            }

            $serials = $this->book->computeSN($book->id);

            $this->view->title      = $book->title;
            $this->view->keywords   = trim($node->keywords . ' ' . $this->config->site->keywords);
            $this->view->node       = $node;
            $this->view->book       = $book;
            $this->view->serials    = $serials;
            $this->view->books      = $this->book->getBookList();
            $this->view->catalog    = $this->book->getFrontCatalog($node->id, $serials);
            $this->view->allCatalog = $this->book->getFrontCatalog($book->id, $serials);
            $this->view->mobileURL  = helper::createLink('book', 'browse', "nodeID=$node->id", $book->id == $node->id ? "book=$book->alias" : "book=$book->alias&node=$node->alias", 'mhtml');
            $this->view->desktopURL = helper::createLink('book', 'browse', "nodeID=$node->id", $book->id == $node->id ? "book=$book->alias" : "book=$book->alias&node=$node->alias", 'html');
        }
        $this->display();
    }

    /**
     * Read an article.
     * 
     * @param  int    $articleID 
     * @access public
     * @return void
     */
    public function read($articleID)
    { 
        $article = $this->book->getNodeByID($articleID);
        if(!$article) die($this->fetch('error', 'index'));
        $book    = $article->book;
        $serials = $this->book->computeSN($book->id);
        
        if($article->type != 'book')
        {        
            $parent  = $article->origins[$article->parent];
            $content = $this->book->addMenu($article->content);
            $this->view->parent      = $parent;
            $this->view->content     = $content;
            $this->view->prevAndNext = $this->book->getPrevAndNext($article);
        }
        $activeLink = $article->type == 'book' ? 'activeSummary' : '';
        $this->view->bookSummaryLink = html::a(inLink('read', "articleID=$book->id", "book=$book->alias&node=$article->alias"), $book->title . $this->lang->book->summary, "class = $activeLink");
        
        $this->view->title       = $article->title . ' - ' . $book->title;;
        $this->view->keywords    = $article->keywords;
        $this->view->desc        = $article->summary;
        $this->view->article     = $article;

        $this->view->book            = $book;
        $this->view->allCatalog      = $this->book->getFrontCatalog($book->id, $serials);
        $this->view->mobileURL       = helper::createLink('book', 'read', "articleID=$article->id", "book=$book->alias&node=$article->alias", 'mhtml');
        $this->view->desktopURL      = helper::createLink('book', 'read', "articleID=$article->id", "book=$book->alias&node=$article->alias", 'html');
        $this->view->books           = $this->book->getBookList();

        $this->dao->update(TABLE_BOOK)->set('views = views + 1')->where('id')->eq($articleID)->exec();

        $this->display();
    }

    /**
     * Admin a book or a chapter.
     * 
     * @params int    $nodeID
     * @access public
     * @return void
     */
    public function admin($nodeID = '')
    {
        if($nodeID) ($node = $this->book->getNodeByID($nodeID)) && $book = $node->book;
        if(!$nodeID or !$node) ($node = $book = $this->book->getFirstBook()) && $nodeID = $node->id;
        if(!$node) $this->locate(inlink('create'));
        $this->view->title    = $this->lang->book->common;
        $this->view->bookList = $this->book->getBookList();
        $this->view->book     = $book;
        $this->view->node     = $node;
        $this->view->catalog  = $this->book->getAdminCatalog($nodeID, $this->book->computeSN($book->id));
        
        $this->display();
    }

    /**
     * Create a book.
     *
     * @access public 
     * @return void
     */
    public function create()
    {
        if($_POST)
        {
            $bookID = $this->book->createBook();
            if($bookID)  $this->send(array('result' => 'success', 'message'=>$this->lang->saveSuccess, 'locate' => inlink('admin', "bookID=$bookID")));
            if(!$bookID) $this->send(array('result' => 'fail', 'message' => dao::getError()));
        }

        $this->view->title    = $this->lang->book->createBook;
        $this->view->bookList = $this->book->getBookList();
        $this->display(); 
    }

    /**
     * Manage catalog of a book or a chapter.
     *
     * @param  int    $node   the node to manage.
     * @access public
     * @return void
     */
    public function catalog($node)
    {
        if($_POST)
        {
            /* First I need to check alias. */
            $return = $this->book->checkAlias();
            if(!$return['result']) 
            {
                $message =  sprintf($this->lang->book->aliasRepeat, join(',', array_unique($return['alias'])));
                $this->send(array('result' => 'fail', 'message' => $message));
            }

            /* No error, save to database. */
            $result = $this->book->manageCatalog($node);
            if($result) $this->send(array('result' => 'success', 'message'=>$this->lang->saveSuccess, 'locate' => $this->post->referer . "#node" . $node));
            $this->send(array('result' => 'fail', 'message' => dao::getError()));
        }

        unset($this->lang->book->typeList['book']);

        $this->view->title    = $this->lang->book->catalog;
        $this->view->node     = $this->book->getNodeByID($node);
        $this->view->children = $this->book->getChildren($node);
        $this->view->bookList = $this->book->getBookList();

        $this->display(); 
    }

    /**
     * Edit a book, a chapter or an article.
     *
     * @param int $nodeID
     * @access public
     * @return void
     */
    public function edit($nodeID)
    {
        $node = $this->book->getNodeByID($nodeID, false);
        $book = $node->book;

        if($_POST)
        {
            $result = $this->book->update($nodeID);
            if($result) $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => $this->post->referer . "#node" . $nodeID));
            $this->send(array('result' => 'fail', 'message' => dao::getError()));
        }

        /* Get option menu without this node's family nodes. */
        $optionMenu = $this->book->getOptionMenu($book->id, $removeRoot = true);
        $families   = $this->book->getFamilies($node);
        foreach($families as $member) unset($optionMenu[$member->id]);

        $this->view->title      = $this->lang->edit . $this->lang->book->typeList[$node->type];
        $this->view->node       = $node;
        $this->view->optionMenu = $optionMenu;
        $this->view->bookList   = $this->book->getBookList();
        $this->display();
    }

    /**
     * Delete a node.
     *
     * @param int $nodeID
     * @retturn void
     */
    public function delete($nodeID)
    {
        if($this->book->delete($nodeID)) $this->send(array('result' => 'success'));
        $this->send(array('result' => 'fail', 'message' => dao::getError()));
    }

    /**
     * sort books 
     * 
     * @access public
     * @return void
     */
    public function sort()
    {
        if($this->book->sort()) $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess));
        $this->send(array('result' => 'fail', 'message' => dao::getError()));
    }


    /**
     * search articles of book
     *
     * @access protect
     * @return void
     */
    public function search($recTotal = 0, $recPerPage = 10, $pageID = 1, $searchWord = '')
    {
        $this->app->loadClass('pager');
        $pager = new pager($recTotal, $recPerPage, $pageID);

        $articles = $this->dao->select('*')->from(TABLE_BOOK)
            ->where(1)
            ->beginIf($searchWord)
            ->andwhere('type', true)->eq('article')
            ->andWhere('title')->like("%{$searchWord}%")
            ->orWhere('keywords')->like("%{$searchWord}%")
            ->orWhere('content')->like("%{$searchWord}%")
            ->orWhere('summary')->like("%{$searchWord}%")
            ->markRight(1)
            ->fi()
            ->orderBy('id_desc')
            ->page($pager)
            ->fetchAll('id'); 

        $this->view->title    = $this->lang->book->searchResults;
        $this->view->articles = $articles;
        $this->view->pager    = $pager;
        $this->view->bookList = $this->book->getBookList();

        $this->display();
    }

    /**
     * Setting.
     * 
     * @access public
     * @return void
     */
    public function setting()
    {
        if($_POST)
        {
            $data = new stdclass();
            $data->index      = $this->post->index;
            $data->fullScreen = $this->post->fullScreen;
            $data->chapter    = $this->post->fullScreen ? 'left' : $this->post->chapter;
            $this->loadModel('setting')->setItems('system.book', $data);

            if(dao::isError()) $this->send(array('result' => 'fail', 'message' => dao::getError()));
            $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess));
        }

        $books = $this->book->getBookPairs();

        $this->view->title     = $this->lang->book->setting; 
        $this->view->books     = array('list' => $this->lang->book->list) + $books;
        $this->view->firstBook = key($books);
        $this->view->bookList  = $this->book->getBookList();
        $this->display();
    }
}    
