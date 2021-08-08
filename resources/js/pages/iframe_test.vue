<template>
  <div>
    <b-container>
      <b-row>
        <iframe
          v-resize="{ log: false }"
          width="100%"
          src="https://local.adapt:8890/assignments/1386/questions/view/104765"
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
