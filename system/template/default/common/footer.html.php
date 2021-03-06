<?php if($extView = $this->getExtViewFile(__FILE__)){include $extView; return helper::cd();}?>
  <div class='blocks all-bottom row' data-region='all-bottom'><?php $this->loadModel('block')->printRegion($layouts, 'all', 'bottom', true);?></div>
  </div></div><?php /* end div.page-content then div.page-wrapper in header.html.php */?>
  <footer id='footer' class='clearfix'>
    <div class='wrapper'>
      <div id='footNav'>
        <?php
        echo html::a($this->createLink('sitemap', 'index'), "<i class='icon-sitemap'></i> " . $lang->sitemap->common, "class='text-link'");
        if(empty($this->config->links->index) && !empty($this->config->links->all)) echo '&nbsp;' . html::a($this->createLink('links', 'index'), "<i class='icon-link'></i> " . $this->lang->link);
        ?>
      </div>
      <span id='copyright'>
        <?php
        $copyright = empty($config->site->copyright) ? '' : $config->site->copyright . '-';
        $contact   = json_decode($config->company->contact);
        $company   = (empty($contact->site) or $contact->site == $this->server->http_host) ? $config->company->name : html::a('http://' . $contact->site, $config->company->name, "target='_blank'");
        echo "&copy; {$copyright}" . date('Y') . ' ' . $company . '&nbsp;&nbsp;';
        ?>
      </span>
      <span id='icpInfo'>
        <?php if(!empty($config->site->icpLink) and !empty($config->site->icpSN)) echo html::a(strpos($config->site->icpLink, 'http://') !== false ? $config->site->icpLink : 'http://' . $config->site->icpLink, $config->site->icpSN, "target='_blank'");?>
        <?php if(empty($config->site->icpLink) and !empty($config->site->icpSN))  echo $config->site->icpSN;?>
        <?php if(!empty($config->site->policeLink) and !empty($config->site->policeSN)) echo html::a(strpos($config->site->policeLink, 'http://') !== false ? $config->site->policeLink : 'http://' . $config->site->policeLink, html::image($webRoot . 'theme/default/default/images/main/police.png'), "target='_blank'");?>
      </span>
      <div id='powerby'>
        <?php
        $chanzhiVersion                   = $config->version;
        $isProVersion                     = strpos($chanzhiVersion, 'pro') !== false;
        if($isProVersion) $chanzhiVersion = str_replace('pro', '', $chanzhiVersion);
        ?>
        <?php printf($lang->poweredBy, $config->version, k(), "<span class='" . ($isProVersion ? 'icon-chanzhi-pro' : 'icon-chanzhi') . "'></span> <span class='name'>" . $lang->chanzhiEPSx . '</span>' . $chanzhiVersion); ?>
      </div>
      <?php if($this->config->site->execInfo == 'show') echo $this->config->execPlaceholder; ?>
    </div>
  </footer>
<?php
if($config->debug) js::import($jsRoot . 'jquery/form/min.js');
if(isset($pageJS)) js::execute($pageJS);

/* Load hook files for current page. */
$extPath      = dirname(__FILE__) . '/ext/';
$extHookRule  = $extPath . 'footer.front.*.hook.php';
$extHookFiles = glob($extHookRule);
if($extHookFiles) foreach($extHookFiles as $extHookFile) include $extHookFile;

/* Load hook file for site.*/
$siteExtPath  = dirname(__FILE__) . DS . "ext/_{$this->app->siteCode}/";
$extHookRule  = $siteExtPath . 'footer.front.*.hook.php';
$extHookFiles = glob($extHookRule);
if($extHookFiles) foreach($extHookFiles as $extHookFile) include $extHookFile;
?>
<a href='#' id='go2top' class='icon-arrow-up' data-toggle='tooltip' title='<?php echo $lang->back2Top; ?>'></a>
</div><?php /* end "div.page-container" in "header.html.php" */ ?>
<?php $qrcode = isset($this->config->ui->QRCode) ? $this->config->ui->QRCode : 1;?>
<?php if($qrcode) include $this->loadModel('ui')->getEffectViewFile('default', 'common', 'qrcode');?>
<div class='hide'><?php if(RUN_MODE == 'front') $this->loadModel('block')->printRegion($layouts, 'all', 'footer');?></div>
<?php if(commonModel::isAvailable('shop')) include TPL_ROOT . 'common/cart.html.php';?>
<?php include TPL_ROOT . 'common/log.html.php';?>
<?php if($this->app->user->account != 'guest' and commonModel::isAvailable('score') and (!isset($this->config->site->resetMaxLoginDate) or $this->config->site->resetMaxLoginDate < date('Y-m-d'))):?>
<script>$.get(createLink('score', 'resetMaxLogin'));</script>
<?php endif;?>
</body>
</html>
