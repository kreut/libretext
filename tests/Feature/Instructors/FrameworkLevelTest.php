<?php

namespace Tests\Feature\Instructors;

use Tests\TestCase;

class FrameworkLevelTest extends TestCase
{

/**
    public
    function owner_can_update_framework_level()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/framework-levels", ['title' => 'newer title', 'framework_level_id' => $this->framework_level->id])
            ->assertJson(['message' => "{$this->framework_level->title} has been changed to newer title."]);


    }

    public
    function non_owner_cannot_update_framework_level()
    {
        $this->actingAs($this->user_2)
            ->patchJson("/api/framework-levels", ['title' => 'newer title', 'framework_level_id' => $this->framework_level->id])
            ->assertJson(['message' => "Cannot update"]);

    }
    public function non_owner_cannot_delete_framework_level(){


    }

    public function optionally_delete_associated_descriptors() {

    }

    public function optionally_move_associated_descriptors_to_a_new_level(){


    }

    public function optionally_move_associated_descriptors_to_a_new_level_must_be_a_valid_level(){


    }

    public function descriptor_action_must_be_valid(){


    }

    public function non_owner_cannot_get_all_children(){


    }

    public function owner_can_get_all_children(){


    }

    public function non_owner_cannot_get_all_levels_from_same_parent() {


    }

    public function owner_can_get_all_levels_from_same_parent() {


    }

    public function owner_can_change_framework_level_position_within_level() {


    }

    public function non_owner_cannot_change_framework_level_position_within_level() {


    }

    public function non_owner_cannot_move_levels(){


    }


    public function cannot_move_to_the_same_level() {


    }

    public function cannot_move_to_lower_level_within_a_given_level() {


    }

    public function cannot_move_if_there_are_too_many_sublevels() {


    }

    public function non_owner_cannot_store_with_descriptors() {


    }

    public function cannot_store_descriptors_with_empty_levels() {


    }

    public function will_not_re_add_descriptor() {


    }

    public function an_upload_needs_correct_headings() {


    }
    public function csv_should_not_be_empty() {



    }

    public function csv_should_not_have_missing_entries_within_the_structure() {


    }

    public function level_must_be_valid_when_storing() {


    }

    public function non_instructors_cannot_store() {


    }

    public function stored_framework_level_description_should_be_valid() {


    }

    public function updated_framework_level_description_should_be_valid() {


    }
**/
}
