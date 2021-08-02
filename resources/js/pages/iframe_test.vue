<template>
  <div>
    <b-container>
      <b-row>
        <iframe
          :key="`non-technology-iframe-${currentPage}`"
          v-resize="{ log: false }"
          width="100%"
          src="https://local.adapt:8890/assignments/949/questions/view/102428"
          frameborder="0"
        />
      </b-row>
    </b-container>
  </div>
</template>
<script>
export default {
  middleware: 'auth'
}
</script>
