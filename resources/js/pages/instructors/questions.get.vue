<template>
  <div>
    <QuestionsGet :parent-assignment-id="parseInt($route.params.assignmentId)"/>
  </div>
</template>

<script>
import QuestionsGet from '~/components/questions/QuestionsGet'
export default {
  components: {
    QuestionsGet
  }
}
</script>

