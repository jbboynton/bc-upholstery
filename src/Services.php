<?php

namespace BC\Upholstery;

class Services {
  public const QUERY_ARG = 'filter-by-upholstery-service';
  public const TAXONOMY_ID = 'related_upholstery_service';

  public static function all($options = ['return_type' => 'object']) {
    $return_type = self::set_return_type($options);

    $query = new \WP_Query([
      'nopaging' => true,
      'post_type' => UpholsteryPostType::ID,
      'fields' => 'ids',
    ]);

    $service_ids = $query->posts ?? [];

    if ($return_type === 'ids') {
      $services = $service_ids;
    } else {
      $services = [];

      array_map(function ($id) use (&$services) {
        $services[] = new Service($id);
      }, $service_ids);
    }

    return $services;
  }

  private static function set_return_type($options) {
    $return_types = ['id', 'name', 'slug', 'object'];
    $return_type = $options['return_type'];

    if (!in_array($return_type, $return_types)) {
      $return_type = 'object';
    }

    return $return_type;
  }

  private static function filter_query_arg() {
    return $_REQUEST[self::QUERY_ARG] ?? false;
  }

  private static function selected($filtered_slug, $service_slug) {
    if ($filtered_slug === $service_slug) {
      $markup = 'selected="selected"';
    }

    return $markup ?? '';
  }

  private static function in_media_library() {
    return (get_current_screen()->base === 'upload' ? true : false);
  }

  public function __construct() {
    add_action('plugins_loaded', [$this, 'create_options_page']);
    add_action('init', [$this, 'create_post_type'], 0);
    add_action('init', [$this, 'create_taxonomy'], 0);
    add_action('init', [$this, 'create_bulk_action'], 0);
    add_action('init', [$this, 'create_terms'], 10);
    add_action('restrict_manage_posts', [$this, 'create_filters']);
    add_action('acf/save_post', [$this, 'set_fields_on_save'], 20);
    add_action('wp_trash_post', [$this, 'destroy_terms']);

    add_filter('parse_query', [$this, 'filter_query']);
  }

  public function create_post_type() {
    new UpholsteryPostType();
  }

  public function create_taxonomy() {
    new RelatedUpholsteryTaxonomy();
  }

  public function create_bulk_action() {
    new BulkAction();
  }

  public function create_options_page() {
    $page_title = UpholsteryPostType::PLURAL_NAME . ' Page';
    $menu_title = UpholsteryPostType::PLURAL_NAME . ' Page';
    $parent_slug = 'edit.php?post_type=' . UpholsteryPostType::ID;

    if (function_exists('acf_add_options_sub_page')) {
      acf_add_options_sub_page([
        'page_title' => $page_title,
        'menu_title' => $menu_title,
        'parent_slug' => $parent_slug,
      ]);
    }
  }

  public function create_terms() {
    foreach (self::all() as $service) {
      $this->create_related_service_term($service);
    }
  }

  public function create_filters($post_type) {
    if (!self::in_media_library()) {
      return;
    }

    $filtered_slug = self::filter_query_arg() ?? '';

    $services = Services::all();
    sort($services);

    $options = [];

    array_map(function ($service) use (&$options, $filtered_slug) {
      $selected = self::selected($filtered_slug, $service->slug());
      $slug = $service->slug();
      $name = $service->name();

      $options[] = "<option value=\"$slug\" $selected>$name</option>";
    }, $services);

    $markup = '<select name=' . self::QUERY_ARG . '>';
    $markup .= '<option value="0">All upholstery services</option>';
    $markup .= implode($options);
    $markup .= '</select>';
    $markup .= '&nbsp;';

    echo $markup;
  }

  public function filter_query($query) {
    if (!(is_admin() && $query->is_main_query())) {
      return $query;
    }

    if (!self::in_media_library()) {
      return $query;
    }

    if (!self::filter_query_arg()) {
      return $query;
    }

    if (self::filter_query_arg() === '0') {
      return $query;
    }

    $query->query_vars['tax_query'] = [
      [
        'taxonomy' => Services::TAXONOMY_ID,
        'field' => 'slug',
        'terms' => self::filter_query_arg(),
      ],
    ];

    return $query;
  }

  public function set_fields_on_save($post_id) {
    if (!$this->will_set_on_save($post_id)) {
      return;
    }

    $service = new Service($post_id);

    $this->set_post_data($service);
    $this->set_post_thumbnail($service);
    $this->create_related_service_term($service);
  }

  public function destroy_terms($post_id) {
    if (!$this->will_set_on_save($post_id)) {
      return;
    }

    $service = new Service($post_id);

    $this->delete_related_service_term($service);
  }

  private function will_set_on_save($id) {
    return (get_post_type($id) == UpholsteryPostType::ID ? true : false);
  }

  private function set_post_data($service) {
    wp_update_post([
      'ID' => $service->id(),
      'post_name' => Helpers::dasherize($service->name()),
      'post_title' => $service->name(),
    ]);
  }

  private function set_post_thumbnail($service) {
    set_post_thumbnail($service->id(), $service->card_image());
  }

  private function create_related_service_term($service) {
    if (get_post_status($service->id()) === 'publish') {
      wp_insert_term($service->name(), self::TAXONOMY_ID);
    }
  }

  private function delete_related_service_term($service) {
    $term = get_term_by('name', $service->name(), self::TAXONOMY_ID);

    wp_delete_term($term->term_id, self::TAXONOMY_ID);
  }
}
