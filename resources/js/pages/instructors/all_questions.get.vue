<template>
  <div>
    <QuestionsGet :parent-question-source="'all_questions'"/>
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
