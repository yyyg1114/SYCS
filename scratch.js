const { execSync } = require('child_process');
const fs = require('fs');

try {
    // 1. Get original file content from git
    let originalContent = execSync('git show HEAD:frontend/index.php', { encoding: 'utf8' });
    
    // 2. Apply viewport meta tag fix
    originalContent = originalContent.replace(
        '<meta name="description" content="SYCS - <?= (\'release_notes_desc\') ?>">',
        '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">\n    <meta name="description" content="SYCS - <?= (\'release_notes_desc\') ?>">'
    );
    
    // 3. Apply __(\'key\') regex replacement
    let newContent = originalContent.replace(/<\?=\s*\('(.*?)'\)\s*\?>/g, '<?= __(\'$1\') ?>');
    
    // 4. Write back to disk
    fs.writeFileSync('frontend/index.php', newContent, 'utf8');
    console.log('Successfully restored and fixed index.php');
} catch (e) {
    console.error(e);
}
