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
    add_action('init', [$this, 'create_post_type'], 0);
    add_action('init', [$this, 'create_taxonomy'], 0);
    add_action('init', [$this, 'create_bulk_action'], 0);
    add_action('init', [$this, 'create_terms'], 10);
    add_action('restrict_manage_posts', [$this, 'create_filters']);
    add_action('acf/save_post', [$this, 'set_fields_on_save'], 20);
    add_action('wp_trash_post', [$this, 'destroy_terms']);
    add_action('wp_before_admin_bar_render', [$this, 'add_admin_bar_link']);

    add_filter('parse_query', [$this, 'filter_query']);
    add_filter('display_post_states', [$this, 'post_states'], 10, 2);
    add_filter(
      'get_sample_permalink_html',
      [$this, 'remove_permalink_edit_button'],
      10,
      2,
    );
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

  public function add_admin_bar_link() {
    global $wp_admin_bar;

    if (!$this->on_archive_page()) {
      return;
    }

    $id = ($this->get_archive_page())->ID;

    $wp_admin_bar->add_menu([
      'id' => 'edit',
      'title' => 'Edit Page',
      'href' => admin_url("post.php?post={$id}&action=edit"),
      'meta' => [
        'class' => 'ab-item',
      ],
    ]);
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

  public function post_states($post_states, $post) {
    if (($this->get_archive_page())->ID === $post->ID) {
      $post_states['bc_upholstery_archive_page'] =
        UpholsteryPostType::PAGE_NAME;
    }

    return $post_states;
  }

  public function remove_permalink_edit_button($html, $id) {
    $edit_button_pattern = '/<span id="edit-slug-buttons">.*<\/span>/';

    if (($this->get_archive_page())->ID === $id) {
      $html = preg_replace($edit_button_pattern, '', $html);
    }

    return $html;
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

  private function on_archive_page() {
    $flag = false;
    $queried_object = get_queried_object();

    $is_post_type_object = ($queried_object instanceof \WP_Post_Type);

    if ($is_post_type_object) {
      $responds_to_name = (property_exists($queried_object, 'name'));
      $on_archive_page =
        ($queried_object->name === UpholsteryPostType::ID ? true : false);

      $flag = ($responds_to_name && $on_archive_page);
    }

    return $flag;
  }

  private function get_archive_page() {
    return get_page_by_path(UpholsteryPostType::SLUG);
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
