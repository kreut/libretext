<template>
  <div>
    <PageTitle title="Canvas Configuration"/>
    <p>
      Please use the following information when configuring Adapt to be integrated with Canvas. Once configured as an
      LTI key,
      please contact us with the Developer key ID (the number associated with the key) so that we can activate the
      connection in
      our database.
    </p>

    <p>
      First, go to your Developer keys page and add an LTI key. When the configuration page opens up choose "Configure
      with the Manual Entry"
      if it's not already there.
    </p>
    <p>
      <span class="font-weight-bold">Title:</span> <span id="title">Adapt</span> <span class="text-info">
          <font-awesome-icon :icon="copyIcon" @click="doCopy('title')"/>
        </span>
    </p>
    <p>
      <span class="font-weight-bold">Description</span> <span id="description">Online homework platform</span>
      <span class="text-info">
          <font-awesome-icon :icon="copyIcon" @click="doCopy('description')"/>
        </span>
    </p>
    <p>
      <span class="font-weight-bold">Redirect URIs:</span>
      <span id="redirect-uri">https://adapt.libretexts.org/api/lti/redirect-uri/{{ campusId }}</span>
      <span class="text-info">
          <font-awesome-icon :icon="copyIcon" @click="doCopy('redirect-uri')"/>
        </span>
    </p>
    <p><span class="font-weight-bold">Target Link URI:</span>
      <span id="target-link-uri">https://adapt.libretexts.org/api/lti/redirect-uri/{{ campusId }}</span>
      <span class="text-info">
          <font-awesome-icon :icon="copyIcon" @click="doCopy('target-link-uri')"/>
        </span>
    </p>
    <p>
      <span class="font-weight-bold">OpenID Connect Initiation Url:</span>
      <span id="open-id-connect-url">https://adapt.libretexts.org/api/lti/oidc-initiation-url</span>
      <span class="text-info">
          <font-awesome-icon :icon="copyIcon" @click="doCopy('open-id-connect-url')"/>
        </span>
    </p>
    <p><span class="font-weight-bold">Public JWK:</span> <span id="public-jwk">{
      "d":
      "tkdUVHX4yVKzkK1pPLKO11QXzteTcBF4QJIVGJ6ZjwBf7WeBIXzMrGli2XFSFum2yygrbkQlTF_Xr3yG5JC1NBK4aj4t0AE3Fy_89a_PmwFKa4aTQIPX73zP2bpFw0YHnejDzTAtdZ7HhKfB1FOKBzcF1ci-hb5rLax8mKBJ5IyIjJN-DtjBYwGr6CCYTNIJKF1Z8UT-TDYtZxj1YSvk32cka4ttMdUYdwrCKt-j1MsQiAlpA-437SxqlXUAX7ooutNCz-b-57h8_Sw7AnmO8USbtHi3Q5O__bpG_H7quv_t1WDGAoWFr6cOA2h_Kgx8WX1szMmiOPPZmpdu5YYHcQ",
      "e": "AQAB",
      "n":
      "8osiSa75nmqmakwNNocLA2N2huWM9At_tjSZOFX1r4-PDclSzxhMw-ZcgHH-E_05Ec6Vcfd75i8Z-Bxu4ctbYk2FNIvRMN5UgWqxZ5Pf70n8UFxjGqdwhUA7_n5KOFoUd9F6wLKa6Oh3OzE6v9-O3y6qL40XhZxNrJjCqxSEkLkOK3xJ0J2npuZ59kipDEDZkRTWz3al09wQ0nvAgCc96DGH-jCgy0msA0OZQ9SmDE9CCMbDT86ogLugPFCvo5g5zqBBX9Ak3czsuLS6Ni9Wco8ZSxoaCIsPXK0RJpt6Jvbjclqb4imsobifxy5LsAV0l_weNWmU2DpzJsLgeK6VVw",
      "p":
      "-TEfpa5kz8Y6jCPJK6u5GMBXIniy1972X_HwyuqcUDZDyy2orr3rRj0sOtJoDHtC62_NnrhuvZYyW-cZ0nDzrzPj8ma-gCpbcgdRfOpEAeA6T_xjfN5KN3u3dHQ7e_qoBtCPJFhiB8Axmjs_NdbwUo0axqQB50QpbRv3gdid0qk",
      "q":
      "-SuCu0BGnaed3VYa7GBAyNf74eNPSn3Ht9MwK1-9iFmC5T0CULHndUcV4Zzp-qwORSYEW_R2oyfDRM_MRCosSUEiHztMZLglJeZxtBx6MjH6vLaQwW7Ixkg-69kKct8H93tC7YNTqZ14gEwT_wBfmQGqfV6R12KgRJ1KQeSSJ_8",
      "dp":
      "aPCeAjjZ7YHuP_wGCOUNUvYU-8hWkIAtwyPxIpMAdusTS6oTwlrqjK7QRIk9FhyGhv2TWwcSY7avyHIfNrcoeBzjHr7T9MdhsTiRwYgqUZvrEqoX_4rhOFJaZKlaL5DUV-JWlZi-18LBYNEYgoTcufcAUqzYvFrBE1jWt5DQjdk",
      "dq":
      "E7OrDJ9SdhjQ1LWAP3yE4tyhIAVXOa6kYhai0mspk2RwgyvFyReoE4_hXQuJPLbqEfGlmpfD4ba9K-26WxFymwA5cHrB2Zzt4wdLqlAuIVXuW4mb_I-D9Jm1z_RDbT3RZXIropglv12iL5LUae9fn7uP_YXCxmMYBRTi0D8Ah4U",
      "qi":
      "YwLEhy55SQucj2vQqSO1dqn2YiB2ARHBA83QKb1PHflkTNGn3mR_gLow-xU7BmTmA2-9CeDHiJrD181gb48XbI24Nn4QXAjS-mYYIpFASR739UI4W5wyyOCMyFtT6OupEgkqKw_swITU1GHKYI-lB_-y0Q-XSdLmuP6ZkkdAQao",
      "alg": "RS256",
      "kid": "58f36e10-c1c1-4df0-af8b-85c857d1634f",
      "kty": "RSA",
      "use": "sig"
      }</span> <span class="text-info">
          <font-awesome-icon :icon="copyIcon" @click="doCopy('public-jwk')"/>
        </span>
    </p>

    <p><span class="font-weight-bold">Under LTI Advantage services toggle the following to On</span></p>
    <ul>
      <li>Can create and view assignment data in the gradebook associated with the tool.</li>
      <li> Can view assignment data in the gradebook associated with the tool.</li>
      <li>Can view submission data for assignments associated with the tool.</li>
      <li> Can create and update submission results for assignments associated with the tool.</li>
      <li> Can retrieve user data associated with the context the tool is installed in.</li>
    </ul>
    <p>
      <span class="font-weight-bold">Below LTI Advantage services, you should see a toggle for Public or Private: choose Public</span>
    </p>
    <p><span class="font-weight-bold">Placements:</span> Assignment Selection </p>
    <p>
      <span class="font-weight-bold">Target Link URI:</span>
      <span id="placement-target-link-uri">https://adapt.libretexts.org/api/lti/configure/{{ campusId }}</span>
      <span class="text-info">
          <font-awesome-icon :icon="copyIcon" @click="doCopy('placement-target-link-uri')"/>
        </span></p>
    <p><span class="font-weight-bold">Select Message Type:</span> LtiDeepLinkingRequest</p>
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
    campusId: '',
    copyIcon: faCopy
  }),
  mounted () {
    this.doCopy = doCopy
    this.campusId = this.$route.params.campusId
  }
}
</script>

<style scoped>

</style>
