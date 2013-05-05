<?php decorate_with('layout_2col') ?>
<?php use_helper('Date') ?>

<?php slot('title') ?>
  <h1>
    <?php echo image_tag('/images/icons-large/icon-people.png') ?>
    <?php echo __('Browse %1% %2%', array(
      '%1%' => $pager->getNbResults(),
      '%2%' => sfConfig::get('app_ui_label_actor'))) ?>
  </h1>
<?php end_slot() ?>

<?php slot('sidebar') ?>
  <div id="facets">

    <?php if (isset($pager->facets['subjects_id'])): ?>
      <?php echo get_partial('search/facet', array(
        'target' => '#facet-subject',
        'label' => __('Subject'),
        'facet' => 'subjects_id',
        'pager' => $pager,
        'filters' => $filters)) ?>
    <?php endif; ?>

    <?php if (isset($pager->facets['digitalObject_mediaTypeId'])): ?>
      <?php echo get_partial('search/facet', array(
        'target' => '#facet-mediatype',
        'label' => __('Media type'),
        'facet' => 'digitalObject_mediaTypeId',
        'pager' => $pager,
        'filters' => $filters)) ?>
    <?php endif; ?>

    <?php if (isset($pager->facets['places_id'])): ?>
      <?php echo get_partial('search/facet', array(
        'target' => '#facet-place',
        'label' => __('Place'),
        'facet' => 'places_id',
        'pager' => $pager,
        'filters' => $filters)) ?>
    <?php endif; ?>

    <?php if (isset($pager->facets['names_id'])): ?>
      <?php echo get_partial('search/facet', array(
        'target' => '#facet-name',
        'label' => __('Name'),
        'facet' => 'names_id',
        'pager' => $pager,
        'filters' => $filters)) ?>
    <?php endif; ?>

  </div>
<?php end_slot() ?>

<?php slot('before-content') ?>
  <ul class="nav nav-tabs">

    <?php if ('lastUpdated' == $sortSetting): ?>
      <li<?php if ('nameDown' != $sf_request->sort && 'nameUp' != $sf_request->sort): ?> class="active"<?php endif; ?>><?php echo link_to(__('Recent changes'), array('sort' => 'updatedDown') + $sf_request->getParameterHolder()->getAll(), array('title' => __('Sort'))) ?></li>
      <li<?php if ('nameDown' == $sf_request->sort || 'nameUp' == $sf_request->sort): ?> class="active"<?php endif; ?>><?php echo link_to(__('Alphabetic'), array('sort' => 'nameUp') + $sf_request->getParameterHolder()->getAll(), array('title' => __('Sort'))) ?></li>
    <?php else: ?>
      <li<?php if ('updatedDown' == $sf_request->sort || 'updatedUp' == $sf_request->sort): ?> class="active"<?php endif; ?>><?php echo link_to(__('Recent changes'), array('sort' => 'updatedDown') + $sf_request->getParameterHolder()->getAll(), array('title' => __('Sort'))) ?></li>
      <li<?php if ('updatedDown' != $sf_request->sort && 'updatedUp' != $sf_request->sort): ?> class="active"<?php endif; ?>><?php echo link_to(__('Alphabetic'), array('sort' => 'nameUp') + $sf_request->getParameterHolder()->getAll(), array('title' => __('Sort'))) ?></li>
    <?php endif; ?>

    <li class="search">
      <form method="get" action="<?php echo url_for(array('module' => 'actor', 'action' => 'browse')) ?>">
        <?php foreach ($sf_request->getGetParameters() as $key => $value): ?>
          <input type="hidden" name="<?php echo esc_entities($key) ?>" value="<?php echo esc_entities($value) ?>"/>
        <?php endforeach; ?>
        <div class="input-append">
          <input type="text" class="span3" name="subquery" value="<?php echo esc_entities($sf_request->subquery) ?>" placeholder="<?php echo __('Search') ?>" />
          <span class="add-on icon-search">
            <input type="submit" value="<?php echo __('Search %1%', array('%1%' => sfConfig::get('app_ui_label_actor'))) ?>"/>
          </span>
        </div>
      </form>
    </li>

  </ul>
<?php end_slot() ?>

<table class="table table-bordered">
  <thead>
    <tr>
      <th>
        <?php echo __('Name') ?>
        <?php if ('lastUpdated' == $sortSetting): ?>
          <?php if ('nameDown' == $sf_request->sort): ?>
            <?php echo link_to(image_tag('up.gif'), array('sort' => 'nameUp') + $sf_request->getParameterHolder()->getAll(), array('title' => __('Sort'))) ?>
          <?php elseif ('nameUp' == $sf_request->sort): ?>
            <?php echo link_to(image_tag('down.gif'), array('sort' => 'nameDown') + $sf_request->getParameterHolder()->getAll(), array('title' => __('Sort'))) ?>
          <?php endif; ?>
        <?php else: ?>
          <?php if (('nameDown' != $sf_request->sort && 'updatedDown' != $sf_request->sort && 'updatedUp' != $sf_request->sort) || ('nameUp' == $sf_request->sort)): ?>
            <?php echo link_to(image_tag('down.gif'), array('sort' => 'nameDown') + $sf_request->getParameterHolder()->getAll(), array('title' => __('Sort'))) ?>
          <?php endif; ?>
          <?php if ('nameDown' == $sf_request->sort): ?>
            <?php echo link_to(image_tag('up.gif'), array('sort' => 'nameUp') + $sf_request->getParameterHolder()->getAll(), array('title' => __('Sort'))) ?>
          <?php endif; ?>
        <?php endif; ?>
      </th>
      <?php if ('nameDown' == $sf_request->sort || 'nameUp' == $sf_request->sort || ('lastUpdated' != $sortSetting && 'updatedDown' != $sf_request->sort && 'updatedUp' != $sf_request->sort) ): ?>
        <th><?php echo __('Type') ?></th>
      <?php else: ?>
        <th>
          <?php echo __('Updated') ?>
          <?php if ('updatedUp' == $sf_request->sort): ?>
            <?php echo link_to(image_tag('up.gif'), array('sort' => 'updatedDown') + $sf_request->getParameterHolder()->getAll(), array('title' => __('Sort'))) ?>
          <?php else: ?>
            <?php echo link_to(image_tag('down.gif'), array('sort' => 'updatedUp') + $sf_request->getParameterHolder()->getAll(), array('title' => __('Sort'))) ?>
          <?php endif; ?>
        </th>
      <?php endif; ?>
      <th><?php echo __('Dates') ?></th>
    </tr>
  </thead><tbody>

    <?php foreach ($pager->getResults() as $hit): ?>
      <?php $doc = $hit->getData() ?>
      <tr class="<?php echo 0 == @++$row % 2 ? 'even' : 'odd' ?>">
        <td>
          <?php echo link_to(get_search_i18n($doc, 'authorizedFormOfName'), array('module' => 'actor', 'slug' => $doc['slug'])) ?>
        </td><td>
          <?php if ('nameDown' == $sf_request->sort || 'nameUp' == $sf_request->sort || ('lastUpdated' != $sortSetting && 'updatedDown' != $sf_request->sort && 'updatedUp' != $sf_request->sort) ): ?>
            <?php if (isset($doc['entityTypeId']) && isset($types[$doc['entityTypeId']])): ?>
              <?php echo $types[$doc['entityTypeId']] ?>
            <?php else: ?>
              <?php echo __('N/A') ?>
            <?php endif; ?>
          <?php else: ?>
            <?php echo format_date($doc['updatedAt'], 'f') ?>
          <?php endif; ?>
        </td><td>
          <?php echo get_search_i18n($doc, 'datesOfExistence') ?>
        </td>
      </tr>
    <?php endforeach; ?>

  </tbody>
</table>

<?php slot('after-content') ?>
  <?php echo get_partial('default/pager', array('pager' => $pager)) ?>
<?php end_slot() ?>
