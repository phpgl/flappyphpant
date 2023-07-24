#version 330 core

in vec2 v_uv;
out vec4 fragment_color;
uniform sampler2D u_texture;

// a uv modifier to move the background
uniform float bgmove;

void main() {             
    vec2 uv = vec2(v_uv.x, v_uv.y);
    uv.x += bgmove;
    fragment_color = texture(u_texture, uv);
}