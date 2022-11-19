<template>
  <span>

    <b-tooltip :target="`add-child-level-${itemId}`"
               delay="750"
               triggers="hover"
    >
      Add a child level to the current framework level.
    </b-tooltip>

    <b-tooltip :target="`add-new-level-${itemId}`"
               delay="750"
               triggers="hover"
    >
      Add a new framework level at the same level as the current one.
    </b-tooltip>

    <b-tooltip :target="`show-level-${itemId}`"
               delay="750"
               triggers="hover"
    >
      Show the action icons for this framework level.
    </b-tooltip>
    <b-tooltip :target="`edit-level-${itemId}`"
               delay="750"
               triggers="hover"
    >
      Edit this framework level.
    </b-tooltip>
    <b-tooltip :target="`delete-level-${itemId}`"
               delay="750"
               triggers="hover"
    >
      Delete this framework level.
    </b-tooltip>
    <b-tooltip :target="`delete-descriptor-${itemId}`"
               delay="750"
               triggers="hover"
    >
      Delete this desciptor.
    </b-tooltip>
    <b-tooltip :target="`edit-descriptor-${itemId}`"
               delay="750"
               triggers="hover"
    >
      Edit this descriptor.
    </b-tooltip>

    <b-tooltip :target="`add-descriptor-${itemId}`"
               delay="750"
               triggers="hover"
    >
      Add a descriptor to this level.
    </b-tooltip>

    <b-tooltip :target="`move-level-${itemId}`"
               delay="750"
               triggers="hover"
    >
      Change the position of the framework level or move it to a new level.
    </b-tooltip>

    <b-tooltip :target="`move-descriptor-${itemId}`"
               delay="750"
               triggers="hover"
    >
      Move the descriptor to a new framework level.
    </b-tooltip>

    <b-tooltip :target="`hide-level-${itemId}`"
               delay="750"
               triggers="hover"
    >
      Hide the action icons for this framework level.
    </b-tooltip>

    <b-tooltip :target="`show-descriptor-${itemId}`"
               delay="750"
               triggers="hover"
    >
      Show the action icons for this descriptor.
    </b-tooltip>
    <b-tooltip :target="`hide-descriptor-${itemId}`"
               delay="750"
               triggers="hover"
    >
      Hide the action icons for this descriptor.
    </b-tooltip>
  </span>
</template>

<script>
export default {
  props: {
    itemId: {
      type: Number,
      default: 0
    }
  }
}
</script>

<style scoped>

</style>
