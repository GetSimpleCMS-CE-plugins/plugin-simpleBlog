# SimpleBlog Plugin for GetSimple CMS

Welcome to **SimpleBlog**—a brilliantly designed plugin that transforms your GetSimple CMS into a powerful, elegant blogging platform. Powered by SQLite3, this plugin offers a seamless experience with a sleek interface and robust features. Great news: SimpleBlog is now **completely free** to use on any project! We’re committed to supporting the GetSimple community, and while this plugin is free, we kindly ask for your support to help us continue developing exciting projects for GetSimple CMS. Ready to elevate your website? Let’s dive in!

![blog7](https://github.com/user-attachments/assets/4ea4f07e-7691-4062-9b58-e10615f9e6fb)

---

## 1. General Overview
- **Name:** SimpleBlog
- **Version:** 2.0
- **Author:** Multicolor
- **Author’s Website:** [http://ko-fi.com/multicolorplugins](http://ko-fi.com/multicolorplugins)
- **Description:** SimpleBlog is a masterpiece of design and functionality, bringing a seamless blogging experience to GetSimple CMS. With its sleek interface, robust features, and SQLite3-powered efficiency, it’s the perfect solution for anyone looking to elevate their website with a professional blog. Download it for free and use it on unlimited sites—our contribution to the GetSimple community!
- **Date:** Fully up-to-date as of April 03, 2025—ready for modern web demands!
- **License Note:** SimpleBlog is now free for everyone to use as often as you like on any project. The only rules? No editing or reselling it as your own creation—keep the brilliance intact! If you find it valuable, please consider supporting our ongoing work for GetSimple CMS.

> Why settle for ordinary when you can have extraordinary? Download SimpleBlog for free today and unlock a world of blogging brilliance. Your support helps us keep creating for GetSimple!

---

![blog1](https://github.com/user-attachments/assets/15244f2e-f2c7-4ed8-bbce-16517af3566a)

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

> This isn’t just a plugin—it’s a work of art, now free for all. Download SimpleBlog and, if it transforms your site, please support our future GetSimple projects!

---

![blog2](https://github.com/user-attachments/assets/b89ab1a3-fb39-4673-be7b-99eee378f348)

## 3. Database Structure
- **Posts Table:**
  - Stores post details like title, slug, content, category, cover photo, date, publication status, scheduled date, and description—a perfect harmony of data.
- **Categories Table:**
  - Manages categories with names and slugs, keeping your blog organized with effortless grace.
- **Comments Table:**
  - Captures comments with post ID, author, email, content, date, and approval status—engaging your readers has never been smoother.
- **Settings Table:**
  - Holds customizable settings like CAPTCHA keys, posts per page, and routing options, tailored to your vision.

> The database design showcases SimpleBlog’s brilliance—now free to use forever. Love it? Help us keep building for GetSimple CMS!

---

![blog3](https://github.com/user-attachments/assets/9508fbb5-3d72-4a88-ba12-4d51f4633ec2)

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

> SimpleBlog’s admin panel is a masterpiece of usability, now free for all. Download it and consider supporting our next GetSimple innovation!

---

![blog4](https://github.com/user-attachments/assets/0768cb80-6fc6-4d73-bd8b-2cd5c36c4d38)

## 5. Frontend Features
- **Content Display (`get_blog_content`):**
  - **Post List:** Showcases posts with pagination, cover photos, titles, dates, and categories—an irresistible presentation.
  - **Single Post:** Delivers full post content, cover photos, and categories with stunning clarity—readers will be hooked.
  - **Category View:** Highlights posts by category with pagination, making navigation a delight.
- **Comments Opt-in:**
  - Adds a seamless comment system with optional CAPTCHA (hCaptcha or reCAPTCHA)—engaging and secure.
- **Helper Functions:**
  - `get_categoryList()`: A beautiful category list with links.
  - `get_newPostList($number)`: Latest posts with titles—simple and effective.
  - `get_newPostListWithDesc($number)`: Posts with descriptions—captivating previews.
  - `get_newPostListFull($number)`: Posts with cover photos and descriptions—stunningly complete.
  - `get_randomPostList($number)`: Random posts for a fresh twist.

> The frontend is where SimpleBlog shines brightest, and it’s free for all. If it captivates your visitors, please support our work for more GetSimple tools!

---

![blog5](https://github.com/user-attachments/assets/d8b5d20a-5d30-4021-8f78-01ee4e28ff38)

## 6. Plugin Settings
- **CAPTCHA:** Supports hCaptcha and reCAPTCHA with enable/disable options—security with style.
- **Slug Routing:** Toggle between elegant slug URLs (e.g., `/blog/post-title`) or classic GET parameters—your choice, flawlessly executed.
- **Posts Per Page:** Set your preferred number (default 10)—customization made simple.
- **Parent Page:** Choose the CMS page for your blog—integration at its best.
- **Comments:** Enable or disable comments with ease—reader engagement on your terms.

> These settings make SimpleBlog a joy to configure, now free. Love the flexibility? Support our future GetSimple projects!

---

![blog6](https://github.com/user-attachments/assets/40d4deee-d92e-4e69-af62-e690cd3b464e)

## 7. Integration with GetSimple CMS
- **Hooks:**
  - `nav-tab`: Adds a sleek tab to the navigation menu.
  - `blog-sidebar`: Builds a stylish sidebar menu for blog management.
  - `index-pretemplate`: Injects blog content into your chosen page—seamless brilliance.
  - `sitemap-aftersave`: Keeps your sitemap fresh and SEO-ready.
- **CSS Styling:** Loads a polished `style.css` file for a consistent, beautiful look.

> SimpleBlog blends into GetSimple CMS like a dream, and it’s now free. If it enhances your site, please help us keep building for GetSimple!

---

## 8. Additional Highlights
- **Slug Generation:** Automatically creates unique slugs with duplicate handling—smart and efficient.
- **Pagination:** Smooth navigation on both frontend and admin sides—user experience perfected.
- **Sitemap:** Dynamic generation for posts and CMS pages—an SEO gem.
- **Media Support:** Add cover photos with a slick file browser—visual appeal made easy.

> Every detail of SimpleBlog screams quality, and it’s free for all. Enjoy it? Support our passion for GetSimple CMS development!

---

## 9. Requirements
- **GetSimple CMS:** Designed for seamless compatibility.
- **PHP:** Requires SQLite3 support for blazing performance.
- **Permissions:** Ensure `data/other` is writable.

> SimpleBlog is ready to shine on your setup, now free. If it fits perfectly, consider supporting our next GetSimple creation!

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

> SimpleBlog makes blogging a breeze, and it’s free. Start creating, and if you love it, support our work for more GetSimple tools!

---

## 11. Why Choose SimpleBlog?
- **Security:** Prepared SQLite3 queries keep your data safe.
- **Design:** Responsive CSS styling for a flawless look on any device.
- **Value:** A premium blogging solution, now completely free for the GetSimple community!
- **License:** SimpleBlog is free to use on any project with no limits. Just don’t edit or resell it as your own—keep this gem as it is!

> SimpleBlog isn’t just a plugin—it’s a game-changer, now free for all. Download it and help us keep the GetSimple spirit alive with your support!

---

## Get It Now!
Ready to transform your site? Visit [https://getsimple-ce.ovh/ce-plugins](https://getsimple-ce.ovh/ce-plugins) to download SimpleBlog for **free**. It’s our gift to the GetSimple community, ready for unlimited use on any project. If SimpleBlog elevates your website, please consider supporting our ongoing work at [http://ko-fi.com/multicolorplugins](http://ko-fi.com/multicolorplugins) to help us create more tools and keep GetSimple thriving. Just don’t edit or resell it—let SimpleBlog’s brilliance shine as designed!
