/**
 * Logo Collision Build Script
 * 
 * Builds Free and Pro versions of the plugin from a single codebase.
 * Uses only native Node.js modules - no external dependencies required.
 * 
 * Usage:
 *   npm run build         - Build both versions
 *   npm run build:free    - Build Free version only
 *   npm run build:pro     - Build Pro version only
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// Configuration
const config = {
    srcDir: path.join(__dirname, '..'),
    distDir: path.join(__dirname, '..', 'dist'),
    pluginSlug: 'logo-collision',
    proPluginSlug: 'logo-collision-pro',

    // Files/folders to exclude from both versions
    excludeAlways: [
        'node_modules',
        'package.json',
        'package-lock.json',
        'build',
        'dist',
        '.git',
        '.gitignore',
        '.github'
    ],

    // Files/folders to exclude from Free version only
    excludeFree: [
        'includes'
    ]
};

/**
 * Read version from main plugin file
 */
function getVersion() {
    const mainFile = path.join(config.srcDir, 'LogoCollision.php');
    const content = fs.readFileSync(mainFile, 'utf8');
    const match = content.match(/Version:\s*([^\s\r\n]+)/);
    return match ? match[1] : '1.0.0';
}

/**
 * Process PHP file content for Free version
 * - Sets LOGO_COLLISION_PRO to false
 * - Removes content between // PRO_START and // PRO_END markers
 */
function processForFree(content) {
    // Replace LOGO_COLLISION_PRO constant
    content = content.replace(
        /define\s*\(\s*['"]LOGO_COLLISION_PRO['"]\s*,\s*true\s*\)/g,
        "define('LOGO_COLLISION_PRO', false)"
    );

    // Remove PRO_START to PRO_END blocks (including the markers)
    // Handles both // comments and <!-- --> HTML comments
    content = content.replace(
        /\/\/\s*PRO_START[\s\S]*?\/\/\s*PRO_END\s*\r?\n?/g,
        ''
    );
    content = content.replace(
        /<!--\s*PRO_START\s*-->[\s\S]*?<!--\s*PRO_END\s*-->\s*\r?\n?/g,
        ''
    );

    return content;
}

/**
 * Process PHP file content for Pro version
 */
function processForPro(content) {
    // Ensure LOGO_COLLISION_PRO is true
    content = content.replace(
        /define\s*\(\s*['"]LOGO_COLLISION_PRO['"]\s*,\s*false\s*\)/g,
        "define('LOGO_COLLISION_PRO', true)"
    );
    return content;
}

/**
 * Update plugin header for Pro version
 */
function updateProHeader(content) {
    content = content.replace(
        /Plugin Name:\s*Logo Collision\s*\r?\n/,
        'Plugin Name: Logo Collision Pro\n'
    );
    return content;
}

/**
 * Check if a path should be excluded
 */
function shouldExclude(relativePath, excludeList) {
    const basename = path.basename(relativePath);
    const parts = relativePath.split(path.sep);

    for (const pattern of excludeList) {
        if (basename === pattern || parts.includes(pattern)) {
            return true;
        }
        // Handle file extensions
        if (pattern.startsWith('*.') && relativePath.endsWith(pattern.slice(1))) {
            return true;
        }
    }
    return false;
}

/**
 * Copy directory recursively with optional processing
 */
function copyDir(src, dest, isPro, excludeList) {
    if (!fs.existsSync(dest)) {
        fs.mkdirSync(dest, { recursive: true });
    }

    const entries = fs.readdirSync(src, { withFileTypes: true });

    for (const entry of entries) {
        const srcPath = path.join(src, entry.name);
        const destPath = path.join(dest, entry.name);
        const relativePath = path.relative(config.srcDir, srcPath);

        if (shouldExclude(relativePath, excludeList)) {
            continue;
        }

        if (entry.isDirectory()) {
            copyDir(srcPath, destPath, isPro, excludeList);
        } else if (entry.isFile()) {
            const ext = path.extname(entry.name).toLowerCase();

            if (ext === '.php') {
                let content = fs.readFileSync(srcPath, 'utf8');

                if (isPro) {
                    content = processForPro(content);
                    if (entry.name === 'LogoCollision.php') {
                        content = updateProHeader(content);
                    }
                } else {
                    content = processForFree(content);
                }

                fs.writeFileSync(destPath, content, 'utf8');
            } else {
                fs.copyFileSync(srcPath, destPath);
            }
        }
    }
}

/**
 * Remove directory recursively
 */
function removeDir(dir) {
    if (fs.existsSync(dir)) {
        fs.rmSync(dir, { recursive: true, force: true });
    }
}

/**
 * Create ZIP using PowerShell (Windows)
 */
function createZip(sourceDir, zipPath, folderName) {
    // Remove existing zip
    if (fs.existsSync(zipPath)) {
        fs.unlinkSync(zipPath);
    }

    const parentDir = path.dirname(sourceDir);
    const sourceName = path.basename(sourceDir);

    // Use PowerShell Compress-Archive
    try {
        execSync(
            `powershell -Command "Compress-Archive -Path '${sourceDir}' -DestinationPath '${zipPath}' -Force"`,
            { stdio: 'pipe' }
        );

        const stats = fs.statSync(zipPath);
        console.log(`  Created: ${path.basename(zipPath)} (${(stats.size / 1024).toFixed(1)} KB)`);
        return true;
    } catch (error) {
        console.error(`  Error creating zip: ${error.message}`);
        return false;
    }
}

/**
 * Build a specific version
 */
function buildVersion(isPro) {
    const version = getVersion();
    const versionType = isPro ? 'Pro' : 'Free';
    const slug = isPro ? config.proPluginSlug : config.pluginSlug;
    const zipName = `${slug}-${version}.zip`;

    console.log(`\nBuilding ${versionType} version v${version}...`);

    const excludeList = isPro
        ? config.excludeAlways
        : [...config.excludeAlways, ...config.excludeFree];

    // Create temp and dist directories
    const tempDir = path.join(config.distDir, 'temp');
    const pluginDir = path.join(tempDir, slug);

    try {
        // Clean directories
        removeDir(tempDir);
        if (!fs.existsSync(config.distDir)) {
            fs.mkdirSync(config.distDir, { recursive: true });
        }

        // Copy and process files
        console.log('  Processing files...');
        copyDir(config.srcDir, pluginDir, isPro, excludeList);

        // Create zip
        const zipPath = path.join(config.distDir, zipName);
        createZip(pluginDir, zipPath, slug);

        // Also create a non-versioned zip
        const latestZipPath = path.join(config.distDir, `${slug}.zip`);
        if (fs.existsSync(latestZipPath)) {
            fs.unlinkSync(latestZipPath);
        }
        fs.copyFileSync(zipPath, latestZipPath);

    } finally {
        // Clean up temp directory
        removeDir(tempDir);
    }
}

/**
 * Main build function
 */
function main() {
    const args = process.argv.slice(2);
    const buildFree = args.length === 0 || args.includes('--free');
    const buildPro = args.length === 0 || args.includes('--pro');

    console.log('===========================================');
    console.log('  Logo Collision Build Script');
    console.log('===========================================');

    try {
        if (buildFree) {
            buildVersion(false);
        }

        if (buildPro) {
            buildVersion(true);
        }

        console.log('\n✓ Build complete!');
        console.log(`  Output: ${config.distDir}`);

    } catch (error) {
        console.error('\n✗ Build failed:', error.message);
        console.error(error.stack);
        process.exit(1);
    }
}

main();
