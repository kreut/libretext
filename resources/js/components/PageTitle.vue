<template>
  <div>
    <h1 style="font-size:32px" class="page-title">
      {{ title }}
    </h1> <hr>
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
