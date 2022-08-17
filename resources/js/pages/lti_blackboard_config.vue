<template>
  <div>
    <PageTitle title="Blackboard Configuration"/>
    <b-container>
      <p>
        Using the following configuration, you can integrate your Blackboard installation with ADAPT via LTI 1.3, also
        known as LTI Advantage. As shown in the video, you'll just need to enter two pieces of information:

      </p><p>
<span class="font-weight-bold">Client ID:</span>
        <span id="client-id">4f726988-88c9-4aea-8959-050ba999b8bb</span>
        <span class="text-info">
          <a href=""
             aria-label="Copy client id"
             @click.prevent="doCopy('client-id')"
          >
          <font-awesome-icon :icon="copyIcon"/>
          </a>
        </span>
      </p>

      <p>
<span class="font-weight-bold">Target Link URI:</span>
        <span id="redirect-uri">https://adapt.libretexts.org/api/lti/redirect-uri</span>
        <span class="text-info">
          <a href=""
             aria-label="Copy redirect uri"
             @click.prevent="doCopy('redirect-uri')"
          >
          <font-awesome-icon :icon="copyIcon"/>
          </a>
        </span>
      </p>
      <h2>Blackboard Admin</h2>
      <div>
        <b-embed
          type="iframe"
          aspect="16by9"
          src="https://www.youtube.com/embed/td95okNF-Lk?rel=0"
          allowfullscreen
        />
        <hr>
      </div>
      <h2>Instructor</h2>
      <div>
        <b-embed
          type="iframe"
          aspect="16by9"
          src="https://www.youtube.com/embed/mQpklvhb6Vo?rel=0"
          allowfullscreen
        />
        <hr>
      </div>
      <h2>Student</h2>
      <div>
        <b-embed
          type="iframe"
          aspect="16by9"
          src="https://www.youtube.com/embed/g-6ARvD6oBQ?rel=0"
          allowfullscreen
        />
      </div>
    </b-container>
  </div>
</template>

<script>
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { doCopy } from '~/helpers/Copy'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

export default {
  components: {
    FontAwesomeIcon
  },
  data: () => ({
    copyIcon: faCopy
  }),
  mounted () {
    this.doCopy = doCopy
  }

}
</script>

<style scoped>

</style>
