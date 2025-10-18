# 🚀 VelocityPHP - QUICK START GUIDE

## Get Your App Running in 5 Minutes!

### Step 1: Setup (2 minutes)

1. **Extract/Place** the framework in your web server directory
   - For XAMPP: `C:\xampp\htdocs\native-mvc`
   - For WAMP: `C:\wamp\www\native-mvc`
   - For other servers: Point to the `public/` folder

2. **Configure Apache** (if needed)
   - Ensure `mod_rewrite` is enabled
   - Restart Apache

3. **Test the Installation**
   - Open browser: `http://localhost/native-mvc/public/`
   - Or if configured: `http://localhost/`
   - You should see the welcome page

### Step 2: Test Zero-Refresh Navigation (1 minute)

Click on any navigation link:
- Home → Dashboard → Users → About

**Notice:** The page content changes instantly without any page refresh! 🎉

Check the browser's network tab - you'll see AJAX requests instead of full page loads.

### Step 3: Create Your First Page (2 minutes)

Let's create a "Contact" page:

1. **Create folder:**
   ```
   src/views/pages/contact/
   ```

2. **Create file:** `src/views/pages/contact/index.php`
   ```php
   <div class="container">
       <h1>Contact Us</h1>
       <p>Get in touch with us!</p>
       
       <form data-ajax action="/api/contact" method="POST">
           <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
           
           <div class="form-group">
               <label>Name</label>
               <input type="text" name="name" required>
           </div>
           
           <div class="form-group">
               <label>Email</label>
               <input type="email" name="email" required>
           </div>
           
           <div class="form-group">
               <label>Message</label>
               <textarea name="message" rows="5" required></textarea>
           </div>
           
           <button type="submit" class="btn btn-primary">Send Message</button>
       </form>
   </div>
   ```

3. **Access it:** Navigate to `http://localhost/contact`

**That's it!** Your page is live with zero-refresh navigation! 🎊

---

## Common Tasks

### Add a Link to Navigation

Edit `src/views/components/navbar.php`:

```php
<ul class="navbar-menu">
    <!-- ... existing links ... -->
    <li><a href="/contact" class="nav-link">Contact</a></li>
</ul>
```

### Create a Dynamic Route

For pages like `/blog/post-1`, `/blog/post-2`:

1. Create: `src/views/pages/blog/[slug]/index.php`

2. Use the parameter:
   ```php
   <h1>Blog: <?php echo htmlspecialchars($slug); ?></h1>
   ```

### Make an AJAX Call

```javascript
NativeApp.api.post('/api/endpoint', {
    key: 'value'
})
.done(function(response) {
    NativeApp.showSuccess('Success!');
});
```

---

## Testing Checklist

✅ Home page loads  
✅ Navigation works without refresh  
✅ Dashboard displays  
✅ Users list shows  
✅ User detail page works (click "View" on a user)  
✅ About page loads  
✅ Forms submit via AJAX  
✅ Notifications appear  

---

## Next Steps

1. ✅ Read the full [README.md](README.md)
2. ✅ Explore the example pages
3. ✅ Create your own pages
4. ✅ Build something awesome!

---

## Need Help?

### Page Not Loading?
- Check if folder structure is correct
- Ensure `index.php` exists in the page folder
- Clear browser cache

### 404 Errors?
- Enable `mod_rewrite` in Apache
- Check `.htaccess` is in `public/` folder

### AJAX Not Working?
- Check browser console for errors
- Ensure jQuery loads before `app.js`

---

**You're ready to build powerful zero-refresh applications! 🚀**
