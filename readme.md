# SimpleBlog Plugin for GetSimple CMS

Welcome to **SimpleBlog**—a brilliantly designed plugin that transforms your GetSimple CMS into a powerful, elegant blogging platform. Powered by SQLite3, this plugin offers a seamless experience with a sleek interface and robust features. Once purchased, you can use it as much as you want on any project—unlimited potential awaits! The only restrictions? You can’t edit or resell it as your own. Ready to elevate your website? Let’s dive in!

---

## 1. General Overview
- **Name:** SimpleBlog
- **Version:** 1.0
- **Author:** Multicolor
- **Author’s Website:** [http://ko-fi.com/multicolorplugins](http://ko-fi.com/multicolorplugins)
- **Description:** SimpleBlog is a masterpiece of design and functionality, bringing a seamless blogging experience to GetSimple CMS. With its sleek interface, robust features, and SQLite3-powered efficiency, it’s the perfect solution for anyone looking to elevate their website with a professional blog. Buy it once, use it forever—unlimited times, unlimited sites!
- **Date:** Fully up-to-date as of April 03, 2025—ready for modern web demands!
- **License Note:** Once purchased, SimpleBlog is yours to use as often as you like. The only rules? No editing or reselling it as your own creation—keep the brilliance intact!

> Why settle for ordinary when you can have extraordinary? Purchase SimpleBlog today and unlock a world of blogging brilliance with lifetime usage!

---

## 2. Key Features
- **Plugin Registration:**
  - Seamlessly integrates into GetSimple CMS with a stunning SVG icon in the navigation panel, making it a joy to access.
  - Adds a beautifully organized admin menu with options like "Add Post," "Posts," "Categories," "Comments," and "Settings"—everything you need at your fingertips.
- **SQLite3 Database:**
  - Creates a lightning-fast `blog.db` file in the `data/other` directory, ensuring top-tier performance with minimal setup.
  - Masterfully structures tables for posts, categories, comments, and settings, showcasing a design that’s both elegant and efficient.
- **Content Management:**
  - Effortlessly add, edit, and delete posts and categories with a user-friendly interface that feels like a dream to use.
  - Schedule posts with precision, giving you full control over your publishing strategy—pure genius!
- **Frontend Experience:**
  - Displays your blog with breathtaking style: post lists, individual posts, and category views that captivate your audience.
  - Dynamic URLs adapt to your preference (slugs or GET parameters), offering flexibility wrapped in sophistication.
- **Sitemap Integration:**
  - Automatically generates and updates a flawless `sitemap.xml` file, boosting your SEO game with every change—brilliant!
- **Editor Integration:**
  - Features CKEditor for crafting posts with ease, complete with a file and image browser that’s simply delightful.

> This isn’t just a plugin—it’s a work of art with unlimited usage rights once purchased. Buy SimpleBlog now and watch your website transform into a blogging powerhouse!

---

## 3. Database Structure
- **Posts Table:**
  - Stores post details like title, slug, content, category, cover photo, date, publication status, scheduled date, and description—a perfect harmony of data.
- **Categories Table:**
  - Manages categories with names and slugs, keeping your blog organized with effortless grace.
- **Comments Table:**
  - Captures comments with post ID, author, email, content, date, and approval status—engaging your readers has never been smoother.
- **Settings Table:**
  - Holds customizable settings like CAPTCHA keys, posts per page, and routing options, tailored to your vision.

> The database design is a testament to SimpleBlog’s brilliance—buy it today, use it forever, and experience perfection!

---

## 4. Admin Features (`blog_admin`)
- **Admin Tabs:**
  - **Posts:** A stunning post list with edit, preview, and delete options, plus search and pagination—managing content has never been this enjoyable.
  - **Add Post:** A gorgeous form for creating posts, complete with title, category, cover photo, description, content, and scheduling—pure elegance.
  - **Edit Post:** Refine your posts with the same intuitive form, making updates a breeze.
  - **Categories:** Add and remove categories with a clean, simple interface—organization at its finest.
  - **Comments:** Approve or delete comments with a beautifully designed layout—reader interaction made effortless.
  - **Settings:** Customize CAPTCHA, routing, posts per page, and parent page with a sleek settings panel—control like never before.
  - **Snippet:** Displays handy PHP functions for frontend use, a thoughtful touch for developers.
- **POST Actions:**
  - Handles every operation with precision, from adding posts to saving settings—a flawless execution.

> SimpleBlog’s admin panel is a masterpiece of usability, yours to use endlessly after a single purchase. Get it now and take your blogging to the next level!

---

## 5. Frontend Features
- **Content Display (`get_blog_content`):**
  - **Post List:** Showcases posts with pagination, cover photos, titles, dates, and categories—an irresistible presentation.
  - **Single Post:** Delivers full post content, cover photos, and categories with stunning clarity—readers will be hooked.
  - **Category View:** Highlights posts by category with pagination, making navigation a delight.
- **Comments:**
  - Adds a seamless comment system with optional CAPTCHA (hCaptcha or reCAPTCHA)—engaging and secure.
- **Helper Functions:**
  - `get_categoryList()`: A beautiful category list with links.
  - `get_newPostList($number)`: Latest posts with titles—simple and effective.
  - `get_newPostListWithDesc($number)`: Posts with descriptions—captivating previews.
  - `get_newPostListFull($number)`: Posts with cover photos and descriptions—stunningly complete.
  - `get_randomPostList($number)`: Random posts for a fresh twist.

> The frontend is where SimpleBlog shines brightest. Purchase it once, use it forever, and mesmerize your visitors!

---

## 6. Plugin Settings
- **CAPTCHA:** Supports hCaptcha and reCAPTCHA with enable/disable options—security with style.
- **Slug Routing:** Toggle between elegant slug URLs (e.g., `/blog/post-title`) or classic GET parameters—your choice, flawlessly executed.
- **Posts Per Page:** Set your preferred number (default 10)—customization made simple.
- **Parent Page:** Choose the CMS page for your blog—integration at its best.
- **Comments:** Enable or disable comments with ease—reader engagement on your terms.

> These settings make SimpleBlog a joy to configure. Buy it now, use it endlessly, and tailor it to your needs!

---

## 7. Integration with GetSimple CMS
- **Hooks:**
  - `nav-tab`: Adds a sleek tab to the navigation menu.
  - `blog-sidebar`: Builds a stylish sidebar menu for blog management.
  - `index-pretemplate`: Injects blog content into your chosen page—seamless brilliance.
  - `sitemap-aftersave`: Keeps your sitemap fresh and SEO-ready.
- **CSS Styling:** Loads a polished `style.css` file for a consistent, beautiful look.

> SimpleBlog blends into GetSimple CMS like a dream, and it’s yours to use as much as you want after one purchase. Get it today and see the magic unfold!

---

## 8. Additional Highlights
- **Slug Generation:** Automatically creates unique slugs with duplicate handling—smart and efficient.
- **Pagination:** Smooth navigation on both frontend and admin sides—user experience perfected.
- **Sitemap:** Dynamic generation for posts and CMS pages—an SEO gem.
- **Media Support:** Add cover photos with a slick file browser—visual appeal made easy.

> Every detail of SimpleBlog screams quality. Don’t miss out—purchase it now and use it forever!

---

## 9. Requirements
- **GetSimple CMS:** Designed for seamless compatibility.
- **PHP:** Requires SQLite3 support for blazing performance.
- **Permissions:** Ensure `data/other` are writable.

> SimpleBlog is ready to shine on your setup. Buy it once, use it endlessly, and start blogging in style!

---

## 10. How to Use
1. **Installation:**
   - Copy the plugin files to the `plugins` folder and activate it—effortless setup.
2. **Configuration:**
   - Set your parent page and routing preferences in "Settings"—customize with ease.
   - Optionally enable CAPTCHA for comment security.
3. **Content Creation:**
   - Add categories in "Categories"—organize like a pro.
   - Create posts in "Add Post"—unleash your creativity.
4. **Frontend Magic:**
   - Use helper functions in your templates to display posts—stunning results await.

> SimpleBlog makes blogging a breeze. Purchase it today, use it forever, and start creating!

---

## 11. Why Choose SimpleBlog?
- **Security:** Prepared SQLite3 queries keep your data safe.
- **Design:** Responsive CSS styling for a flawless look on any device.
- **Value:** A premium blogging solution at an unbeatable price—buy once, use unlimited times!
- **License:** Once purchased, SimpleBlog is yours to use as much as you want on any project. The only restrictions are no editing or reselling it as your own—keep this gem as it is!

> SimpleBlog isn’t just a plugin—it’s a game-changer with lifetime usage rights. Don’t wait—buy it now and elevate your website with the best blogging tool for GetSimple CMS!

---

## Get It Now!
Ready to transform your site? Visit [http://ko-fi.com/multicolorplugins](http://ko-fi.com/multicolorplugins) and grab SimpleBlog today. One purchase unlocks unlimited use—your audience deserves the best, so give it to them with this exceptional plugin! Just remember: enjoy it as much as you like, but don’t edit or resell it—let SimpleBlog’s brilliance shine as designed!
