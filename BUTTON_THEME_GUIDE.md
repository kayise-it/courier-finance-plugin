# 08600 Button Theme System Guide

## 🎨 **Unified Button Theme System**

Your app now has a **consistent button theme** that follows the **60-30-10 color rule** with **Blue (#2563eb)** as the primary color.

---

## 🚀 **Quick Start**

### **Basic Button Usage**
```php
// Primary button (Blue - 60%)
echo KIT_Commons::renderButton('Create Waybill', 'primary');

// Secondary button (Gray - 30%)  
echo KIT_Commons::renderButton('Cancel', 'secondary');

// Success button (Green - 10%)
echo KIT_Commons::renderButton('Save', 'success');

// Danger button (Red - 10%)
echo KIT_Commons::renderButton('Delete', 'danger');
```

---

## 🎯 **Button Types**

### **1. Primary Buttons (60% - Blue)**
```php
// Standard primary button
echo KIT_Commons::renderButton('Create Waybill', 'primary');

// With different sizes
echo KIT_Commons::renderButton('Small', 'primary', 'sm');
echo KIT_Commons::renderButton('Large', 'primary', 'lg');
echo KIT_Commons::renderButton('Extra Large', 'primary', 'xl');

// Full width
echo KIT_Commons::renderButton('Full Width', 'primary', 'md', ['fullWidth' => true]);
```

### **2. Secondary Buttons (30% - Gray)**
```php
echo KIT_Commons::renderButton('Cancel', 'secondary');
echo KIT_Commons::renderButton('Back', 'secondary', 'sm');
```

### **3. Success Buttons (10% - Green)**
```php
echo KIT_Commons::renderButton('Save', 'success');
echo KIT_Commons::renderButton('Approve', 'success');
```

### **4. Danger Buttons (10% - Red)**
```php
echo KIT_Commons::renderButton('Delete', 'danger');
echo KIT_Commons::renderButton('Remove', 'danger');
```

### **5. Warning Buttons (10% - Orange)**
```php
echo KIT_Commons::renderButton('Warning', 'warning');
echo KIT_Commons::renderButton('Archive', 'warning');
```

---

## 🎨 **Button Variants**

### **Outline Buttons**
```php
echo KIT_Commons::renderButton('Outline Primary', 'outline-primary');
echo KIT_Commons::renderButton('Outline Secondary', 'outline-secondary');
```

### **Ghost Buttons**
```php
echo KIT_Commons::renderButton('Ghost', 'ghost');
echo KIT_Commons::renderButton('Ghost Primary', 'ghost-primary');
```

### **Link Buttons**
```php
echo KIT_Commons::renderButton('View Details', 'link');
```

---

## 📏 **Button Sizes**

| Size | Classes | Usage |
|------|---------|-------|
| `sm` | `px-4 py-2 text-xs` | Small buttons |
| `md` | `px-6 py-3 text-sm` | **Default** size |
| `lg` | `px-8 py-4 text-base` | Large buttons |
| `xl` | `px-10 py-5 text-lg` | Extra large buttons |

---

## ⚙️ **Advanced Options**

### **Button with Link**
```php
echo KIT_Commons::renderButton('Go to Dashboard', 'primary', 'md', [
    'href' => '?page=dashboard'
]);
```

### **Button with OnClick**
```php
echo KIT_Commons::renderButton('Delete Item', 'danger', 'sm', [
    'onclick' => 'confirmDelete()'
]);
```

### **Button with Icon**
```php
echo KIT_Commons::renderButton('Add New', 'primary', 'md', [
    'icon' => '<path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/>',
    'iconPosition' => 'left'
]);
```

### **Loading Button**
```php
echo KIT_Commons::renderButton('Saving...', 'primary', 'md', [
    'loading' => true
]);
```

### **Disabled Button**
```php
echo KIT_Commons::renderButton('Submit', 'primary', 'md', [
    'disabled' => true
]);
```

### **Full Width Button**
```php
echo KIT_Commons::renderButton('Submit Form', 'primary', 'md', [
    'fullWidth' => true
]);
```

---

## 🎯 **Special Button Types**

### **Tab Buttons**
```php
// Active tab
echo '<button class="' . KIT_Commons::buttonTab(true) . '">Active Tab</button>';

// Inactive tab  
echo '<button class="' . KIT_Commons::buttonTab(false) . '">Inactive Tab</button>';
```

### **Toggle Buttons**
```php
// Active toggle
echo '<button class="' . KIT_Commons::buttonToggle(true) . '">Active</button>';

// Inactive toggle
echo '<button class="' . KIT_Commons::buttonToggle(false) . '">Inactive</button>';
```

### **Icon Buttons**
```php
echo '<button class="' . KIT_Commons::buttonIcon('md') . ' ' . KIT_Commons::buttonPrimary('md') . '">
    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/>
    </svg>
</button>';
```

---

## 🔄 **Migration Guide**

### **Old Button Style → New Style**

| Old | New |
|-----|-----|
| `class="button button-primary"` | `KIT_Commons::renderButton('Text', 'primary')` |
| `class="button button-secondary"` | `KIT_Commons::renderButton('Text', 'secondary')` |
| `style="background: #2563eb"` | `KIT_Commons::renderButton('Text', 'primary')` |
| `style="background: #f3f4f6"` | `KIT_Commons::renderButton('Text', 'secondary')` |

### **Example Migration**
```php
// OLD WAY
<a href="?page=waybill-create" class="button button-primary" style="text-decoration: none;">
    Create Waybill
</a>

// NEW WAY
echo KIT_Commons::renderButton('Create Waybill', 'primary', 'md', [
    'href' => '?page=waybill-create'
]);
```

---

## 🎨 **Color Palette**

### **Primary Colors (60%)**
- **Blue**: `#2563eb` (Primary actions)
- **Blue Hover**: `#1d4ed8`
- **Blue Active**: `#1e40af`

### **Secondary Colors (30%)**
- **Gray**: `#f3f4f6` (Secondary actions)
- **Gray Hover**: `#e5e7eb`
- **Gray Active**: `#d1d5db`

### **Accent Colors (10%)**
- **Green**: `#059669` (Success actions)
- **Red**: `#dc2626` (Danger actions)
- **Orange**: `#ea580c` (Warning actions)

---

## 📱 **Responsive Design**

All buttons are **mobile-responsive** and automatically adjust:
- **Desktop**: Full padding and text size
- **Mobile**: Reduced padding and text size
- **Touch-friendly**: Minimum 44px touch targets

---

## ♿ **Accessibility Features**

- **Focus states**: Clear blue outline on focus
- **Keyboard navigation**: Full keyboard support
- **Screen readers**: Proper ARIA labels
- **High contrast**: Meets WCAG guidelines
- **Disabled states**: Clear visual feedback

---

## 🚀 **Quick Examples**

### **Dashboard Action Buttons**
```php
echo '<div class="flex gap-4">';
echo KIT_Commons::renderButton('Create Waybill', 'primary', 'lg', [
    'href' => '?page=waybill-create'
]);
echo KIT_Commons::renderButton('View All', 'secondary', 'lg', [
    'href' => '?page=waybill-manage'
]);
echo '</div>';
```

### **Form Buttons**
```php
echo '<div class="flex gap-3 justify-end">';
echo KIT_Commons::renderButton('Cancel', 'secondary', 'md');
echo KIT_Commons::renderButton('Save Draft', 'outline-primary', 'md');
echo KIT_Commons::renderButton('Submit', 'primary', 'md');
echo '</div>';
```

### **Table Action Buttons**
```php
echo '<div class="flex gap-2">';
echo KIT_Commons::renderButton('View', 'ghost-primary', 'sm', [
    'href' => '?page=view&id=' . $id
]);
echo KIT_Commons::renderButton('Edit', 'outline-primary', 'sm', [
    'href' => '?page=edit&id=' . $id
]);
echo KIT_Commons::renderButton('Delete', 'danger', 'sm', [
    'onclick' => 'confirmDelete(' . $id . ')'
]);
echo '</div>';
```

---

## ✅ **Benefits**

1. **🎨 Consistent Design**: All buttons follow the same design system
2. **⚡ Easy to Use**: Simple function calls with clear parameters
3. **📱 Responsive**: Works perfectly on all devices
4. **♿ Accessible**: Built-in accessibility features
5. **🔧 Maintainable**: Centralized styling system
6. **🎯 Scalable**: Easy to add new button types
7. **🚀 Performance**: Uses Tailwind CSS for optimal performance

---

## 🎯 **Next Steps**

1. **Replace old buttons** with the new system
2. **Use consistent sizing** across your app
3. **Follow the color hierarchy** (60-30-10 rule)
4. **Test on mobile devices** for responsiveness
5. **Ensure accessibility** compliance

---

**🎉 Your app now has a beautiful, consistent button theme system!**
