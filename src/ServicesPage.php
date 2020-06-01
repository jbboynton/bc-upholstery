<?php

namespace BC\Upholstery;

class ServicesPage {
  private $id;
  private $banner;
  private $intro_copy;
  private $featured_copy;
  private $featured_services;

  public function __construct() {
    $this->id = get_page_by_path(UpholsteryPostType::SLUG)->ID ?? '';
    $this->set_banner();
    $this->set_intro_copy();
    $this->set_featured();
  }

  public function id() {
    return $this->id;
  }

  public function name() {
    return get_post_type_object(UpholsteryPostType::ID)->labels->name;
  }

  public function banner($size = 'full') {
    return wp_get_attachment_image_url($this->banner, $size);
  }

  public function link() {
    return get_post_type_archive_link(UpholsteryPostType::ID);
  }

  public function intro_copy() {
    return $this->intro_copy;
  }

  public function featured_copy() {
    return $this->featured_copy;
  }

  public function featured_services() {
    $published_services = Service::find_published(
      UpholsteryPostType::ID,
      $this->featured_services
    );

    return array_map(function ($id) {
      return new Service($id);
    }, $published_services);
  }

  private function set_banner() {
    $this->banner = get_field('bc_upholstery_page_images', $this->id)['banner'];
  }

  private function set_intro_copy() {
    $this->intro_copy = get_field('bc_upholstery_page_intro_copy', $this->id);
  }

  private function set_featured() {
    $featured = get_field('bc_upholstery_page_featured', $this->id);

    $this->featured_copy = [
      'heading' => $featured['heading'],
      'subheading' => $featured['subheading'],
    ];

    $this->featured_services = $featured['services'];
  }
}
