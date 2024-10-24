<template>
  <div>
    <PageTitle title="Notifications"/>
    <Notifications/>
  </div>
</template>
<script>
import LinkedAccounts from '~/components/LinkedAccounts.vue'
import PageTitle from '../components/PageTitle.vue'
import Notifications from './settings/notifications.vue'

export default {
  components: { Notifications, PageTitle }
}
</script>
