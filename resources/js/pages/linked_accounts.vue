<template>
  <div>
  <PageTitle title="Linked Accounts"/>
  <LinkedAccounts/>
  </div>
</template>
<script>
import LinkedAccounts from '~/components/LinkedAccounts.vue'
import PageTitle from '../components/PageTitle.vue'

export default {
  components: { PageTitle, LinkedAccounts }
}
</script>
