<template>
  <div class="main-layout">
    <navbar />

    <div class="container mt-4" style="min-height: 700px">
      <child />
    </div>

  <div class="d-flex flex-column" style="background: #e5f5fe">
    <footer class="footer" style="border:1px solid #30b3f6">
      <p class="pt-3 pl-3 pr-4">The LibreTexts libraries are&nbsp;<a href="http://www.mindtouch.com/" rel="external nofollow" target="_blank" title="MindTouch v.16.7">Powered by MindTouch<sup>&reg;</sup>&nbsp;</a>and are supported by the Department of Education Open Textbook Pilot Project, the UC Davis Office of the Provost, the UC Davis Library, the California State University Affordable Learning Solutions Program, and Merlot. We also acknowledge previous National Science Foundation support under grant numbers 1246120, 1525057, and 1413739. Unless otherwise noted, LibreTexts content is licensed by&nbsp;<a href="http://creativecommons.org/licenses/by-nc-sa/3.0/us/" rel="license">CC BY-NC-SA 3.0</a>. Have questions or comments? For more information contact us at&nbsp;<a title="mailto:info@libretexts.org" href="mailto:info@libretexts.org" rel="external nofollow" target="_blank" class="link-mailto">info@libretexts.org</a>&nbsp;or check out our status page at <a title="Footer" href="https://status.libretexts.org" rel="external nofollow" target="_blank" class="link-https">https://status.libretexts.org</a>.</p>

      <div class="d-flex  justify-content-left flex-wrap">
        <a class="ml-5 pt-3 pb-3" href="https://www.ed.gov/news/press-releases/us-department-education-awards-49-million-grant-university-california-davis-develop-free-open-textbooks-program" rel="external nofollow" target="_blank" title="https://www.ed.gov/news/press-releases/us-department-education-awards-49-million-grant-university-california-davis-develop-free-open-textbooks-program"> <img alt="DOE Logo.png"  src="https://adapt.libretexts.org/assets/img/DOE.png" /></a>
        <a class="ml-5 pt-3 pb-3" href="https://blog.libretexts.org/2020/03/21/libretext-project-announces-1-million-california/" rel="external nofollow" target="_blank" title="https://blog.libretexts.org/2020/03/21/libretext-project-announces-1-million-california/"> <img alt="DOE Logo.png" style="height:85px;"  src="https://adapt.libretexts.org/assets/img/CELL_LogoColor.png" /></a>   </div>
    </footer>
  </div>
  </div>
</template>

<script>
import Navbar from '~/components/Navbar'


export default {
  name: 'MainLayout',
  components: {
    Navbar,
  }
}
</script>

