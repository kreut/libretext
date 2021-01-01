<template>
  <div>
    <h2 class="page-title">
      {{ title }}
    </h2> <hr>
  </div>
</template>

<script>
export default {
  name: 'PageTitle',

  props: { title: {
    type: String,
    default: ''
  }
  }
}
</script>
